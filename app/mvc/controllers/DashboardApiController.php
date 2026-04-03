<?php

namespace App\Controllers;


use App\Core\database\Database;
use App\Core\mvc\Controller;
use App\Core\services\DashboardApiQueryService;
use App\Core\services\DashboardApiResourceService;
use App\Models\Language;use BadMethodCallException;
use Closure;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Error;
use ErrorException;
use Exception;
use FilesystemIterator;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;
use Throwable;
use stdClass;

/**
 * Dashboard API Controller
 * 
 * RESTful API controller for dashboard CRUD operations
 * Supports multiple apps (users, blog, etc.) with extensible custom actions
 */
class DashboardApiController extends Controller
{
    private DashboardApiResourceService $resourceService;
    private DashboardApiQueryService $queryService;

    public function __construct(
        ?DashboardApiResourceService $resourceService = null,
        ?DashboardApiQueryService $queryService = null
    ) {
        parent::__construct();

        $this->resourceService = $resourceService ?? new DashboardApiResourceService();
        $this->queryService = $queryService ?? new DashboardApiQueryService();
    }

    /**
     * List all resources for an app
     * 
     * Route: GET /dashboard/{app}
     */
    public function index(string $app): void
    {
        $modelClass = $this->getModelClass($app);
        if (!$modelClass) {
            $this->error("App '{$app}' not found", null, 404);
        }

        try {
            // Get query parameters for filtering/pagination
            $page = (int) $this->request->query('page', 1);
            $limit = (int) $this->request->query('limit', 50);
            $search = $this->request->query('search', '');
            $sort = $this->request->query('sort', 'id');
            $order = $this->request->query('order', 'desc');
            $languageId = $this->request->query('language_id', null);
            $languageCode = $this->request->query('language_code', null);
            $dynamicFilters = $this->queryService->collectDynamicFilters($this->request->query());

            // Special handling for blog-posts (needs category filter via many-to-many)
            if ($app === 'blog-posts' || $app === 'blog') {
                $resourcesArray = $this->getBlogPostsWithRelations($page, $limit, $search, $sort, $order, $languageId, $languageCode, $dynamicFilters);
                $total = $this->getBlogPostsCount($search, $languageId, $languageCode, $dynamicFilters);
            } elseif ($app === 'blog-categories') {
                // Special handling for blog-categories (needs parent_name and depth)
                $resourcesArray = $this->getBlogCategoriesWithRelations($page, $limit, $search, $sort, $order, $languageId, $languageCode);
                $total = $this->getBlogCategoriesCount($search, $languageId, $languageCode);
            } elseif ($app === 'blog-tags') {
                // Special handling for blog-tags (needs post_count)
                $resourcesArray = $this->getBlogTagsWithRelations($page, $limit, $search, $sort, $order, $languageId, $languageCode);
                $total = $this->getBlogTagsCount($search, $languageId, $languageCode);
            } elseif ($app === 'languages') {
                // Special handling for languages (needs continent and region search)
                $result = $this->getLanguagesWithRelations($page, $limit, $search, $sort, $order, $dynamicFilters);
                $resourcesArray = $result['data'];
                $total = $result['total'];
            } else {
                // Build query
                $query = $modelClass::query();

                // Special handling for users - exclude soft deleted
                if ($app === 'users') {
                    // Check if deleted_at column exists
                    try {
                        $columns = Database::select("SHOW COLUMNS FROM users LIKE 'deleted_at'");
                        if (!empty($columns)) {
                            $query->whereNull('deleted_at');
                        }
                    } catch (Exception $e) {
                        // Column doesn't exist, continue
                    }
                }

                // Apply language filter if provided
                if ($languageId !== null && $languageId !== '') {
                    $query->where('language_id', (int) $languageId);
                } elseif ($languageCode !== null && $languageCode !== '' && class_exists('Language')) {
                    $language = Language::findByCode($languageCode);
                    if ($language) {
                        $query->where('language_id', $language->id);
                    }
                }

                // Apply search if provided
                if (!empty($search)) {
                    $this->applySearchToQuery($query, $search, $modelClass);
                }

                // Apply dynamic filters
                $this->applyDynamicFilters($query, $dynamicFilters, $app);

                // Get total count before pagination
                $total = $query->count();

                // Apply sorting
                $query->orderBy($sort, $order);

                // Apply pagination
                $offset = ($page - 1) * $limit;
                $results = $query->limit($limit)->offset($offset)->get();

                // Convert to array and enrich with relationships if needed
                $resourcesArray = [];
                foreach ($results as $result) {
                    $instance = new $modelClass();
                    $resource = $instance->newFromBuilder($result);
                    $resourceArray = $resource->toArray();
                    
                    // Enrich with relationships for specific apps
                    $resourceArray = $this->enrichResourceData($app, $resource, $resourceArray);
                    
                    $resourcesArray[] = $resourceArray;
                }
            }

            $this->success([
                'data' => $resourcesArray,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit)
                ]
            ], "{$app} retrieved successfully");
        } catch (Exception $e) {
            $this->error("Failed to retrieve {$app}: " . $e->getMessage(), null, 500);
        }
    }

    /**
     * Apply dynamic filters to query builder
     */
    private function applyDynamicFilters($query, array $filters, string $app): void
    {
        $this->queryService->applyDynamicFilters($query, $filters);
    }

    /**
     * Apply search to query builder
     * Groups all search conditions together so they combine properly with other filters
     */
    private function applySearchToQuery($query, string $search, string $modelClass): void
    {
        $this->queryService->applySearchToQuery($query, $search, $modelClass);
    }

    /**
     * Enrich resource data with relationships
     */
    private function enrichResourceData(string $app, $resource, array $resourceArray): array
    {
        return $this->queryService->enrichResourceData($app, $resource, $resourceArray);
    }

    /**
     * Get blog categories with parent_name and depth using raw SQL
     */
    private function getBlogCategoriesWithRelations(int $page, int $limit, string $search, string $sort, string $order, ?int $languageId, ?string $languageCode): array
    {
        return $this->queryService->getBlogCategoriesWithRelations(
            $page,
            $limit,
            $search,
            $sort,
            $order,
            $languageId,
            $languageCode
        );
    }

    /**
     * Get blog tags with post_count using raw SQL
     */
    private function getBlogTagsWithRelations(int $page, int $limit, string $search, string $sort, string $order, ?int $languageId, ?string $languageCode): array
    {
        return $this->queryService->getBlogTagsWithRelations(
            $page,
            $limit,
            $search,
            $sort,
            $order,
            $languageId,
            $languageCode
        );
    }

    /**
     * Get count of blog categories with filters
     */
    private function getBlogCategoriesCount(string $search, ?int $languageId, ?string $languageCode): int
    {
        return $this->queryService->getBlogCategoriesCount($search, $languageId, $languageCode);
    }

    /**
     * Get count of blog tags with filters
     */
    private function getBlogTagsCount(string $search, ?int $languageId, ?string $languageCode): int
    {
        return $this->queryService->getBlogTagsCount($search, $languageId, $languageCode);
    }

    /**
     * Get blog posts with relations and filters using raw SQL
     */
    private function getBlogPostsWithRelations(int $page, int $limit, string $search, string $sort, string $order, ?int $languageId, ?string $languageCode, array $filters = []): array
    {
        return $this->queryService->getBlogPostsWithRelations(
            $page,
            $limit,
            $search,
            $sort,
            $order,
            $languageId,
            $languageCode,
            $filters
        );
    }

    /**
     * Get count of blog posts with filters
     */
    private function getBlogPostsCount(string $search, ?int $languageId, ?string $languageCode, array $filters = []): int
    {
        return $this->queryService->getBlogPostsCount(
            $search,
            $languageId,
            $languageCode,
            $filters
        );
    }

    /**
     * Get languages with continent and region relations using raw SQL
     * Includes search in continent and region names
     */
    private function getLanguagesWithRelations(int $page, int $limit, string $search, string $sort, string $order, array $filters = []): array
    {
        return $this->queryService->getLanguagesWithRelations(
            $page,
            $limit,
            $search,
            $sort,
            $order,
            $filters
        );
    }

    /**
     * Show single resource
     * 
     * Route: GET /dashboard/{app}/{id}/show
     */
    public function show(string $app, int $id): void
    {
        $modelClass = $this->getModelClass($app);
        if (!$modelClass) {
            $this->error("App '{$app}' not found", null, 404);
        }

        try {
            $resource = $modelClass::find($id);
            
            if (!$resource) {
                $this->error("Resource not found", null, 404);
            }

            $this->success($resource->toArray(), "{$app} retrieved successfully");
        } catch (Exception $e) {
            $this->error("Failed to retrieve {$app}: " . $e->getMessage(), null, 500);
        }
    }

    /**
     * Create new resource
     * 
     * Route: POST /dashboard/{app}/create
     */
    public function create(string $app): void
    {
        $modelClass = $this->getModelClass($app);
        if (!$modelClass) {
            $this->error("App '{$app}' not found", null, 404);
        }

        // Get validation rules for this app
        $validationRules = $this->getValidationRules($app, 'create');
        
        // Validate input
        $validation = $this->validate($validationRules);
        if ($validation !== true) {
            $this->validationError($validation);
        }

        try {
            $data = $this->prepareData($app, $this->request->all());
            $resource = new $modelClass();
            
            // Fill resource with data
            foreach ($data as $key => $value) {
                if (property_exists($resource, $key) || method_exists($resource, 'setAttribute')) {
                    $resource->$key = $value;
                }
            }

            // Special handling for users
            if ($app === 'users' && isset($data['password'])) {
                $resource->updatePassword($data['password']);
                unset($data['password']);
            }

            $resource->save();

            $this->success($resource->toArray(), "{$app} created successfully", 201);
        } catch (Exception $e) {
            $this->error("Failed to create {$app}: " . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update resource
     * 
     * Route: POST /dashboard/{app}/{id}/update or PUT /dashboard/{app}/{id}
     */
    public function update(string $app, int $id): void
    {
        $modelClass = $this->getModelClass($app);
        if (!$modelClass) {
            $this->error("App '{$app}' not found", null, 404);
        }

        try {
            $resource = $modelClass::find($id);
            
            if (!$resource) {
                $this->error("Resource not found", null, 404);
            }

            // Get validation rules for this app
            $validationRules = $this->getValidationRules($app, 'update');
            
            // Validate input
            $validation = $this->validate($validationRules);
            if ($validation !== true) {
                $this->validationError($validation);
            }

            $data = $this->prepareData($app, $this->request->all(), $resource);
            
            // Update resource
            foreach ($data as $key => $value) {
                if (property_exists($resource, $key) || method_exists($resource, 'setAttribute')) {
                    $resource->$key = $value;
                }
            }

            // Special handling for users
            if ($app === 'users' && isset($data['password']) && !empty($data['password'])) {
                $resource->updatePassword($data['password']);
            }

            $resource->save();

            $this->success($resource->toArray(), "{$app} updated successfully");
        } catch (Exception $e) {
            $this->error("Failed to update {$app}: " . $e->getMessage(), null, 500);
        }
    }

    /**
     * Delete resource
     * 
     * Route: DELETE /dashboard/{app}/{id}/delete
     */
    public function delete(string $app, int $id): void
    {
        $modelClass = $this->getModelClass($app);
        if (!$modelClass) {
            $this->error("App '{$app}' not found", null, 404);
        }

        try {
            $resource = $modelClass::find($id);
            
            if (!$resource) {
                $this->error("Resource not found", null, 404);
            }

            // Special check for users - prevent deleting yourself
            if ($app === 'users' && $resource->id == ($_SESSION['user_id'] ?? 0)) {
                $this->error('Cannot delete your own account', null, 403);
            }

            $resource->delete();

            $this->success(null, "{$app} deleted successfully");
        } catch (Exception $e) {
            $this->error("Failed to delete {$app}: " . $e->getMessage(), null, 500);
        }
    }

    /**
     * Handle custom actions for specific apps
     * 
     * Route: POST /dashboard/{app}/{id}/{action}
     */
    /**
     * Get filter options dynamically based on current filters
     * 
     * Route: GET /api/dashboard/filter-options/{filterType}?language_id=1&continent_id=2
     */
    public function getFilterOptions(string $filterType): void
    {
        try {
            $options = $this->queryService->getFilterOptions(
                $filterType,
                $this->request->query('language_id') !== null
                    ? (int) $this->request->query('language_id')
                    : null,
                $this->request->query('continent_id') !== null
                    ? (int) $this->request->query('continent_id')
                    : null
            );
            
            $this->success($options, 'Filter options retrieved successfully');
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage(), null, 400);
        } catch (Exception $e) {
            $this->error('Failed to get filter options: ' . $e->getMessage(), null, 500);
        }
    }
    
    public function action(string $app, int $id, string $action): void
    {
        $modelClass = $this->getModelClass($app);
        if (!$modelClass) {
            $this->error("App '{$app}' not found", null, 404);
        }

        // Check if action is allowed for this app
        if (!$this->resourceService->actionAllowed($app, $action)) {
            $this->error("Action '{$action}' not available for app '{$app}'", null, 404);
        }

        try {
            $resource = $modelClass::find($id);
            
            if (!$resource) {
                $this->error("Resource not found", null, 404);
            }

            // Handle specific actions
            $result = $this->handleCustomAction($app, $action, $resource);

            $this->success($result, "Action '{$action}' executed successfully");
        } catch (Exception $e) {
            $this->error("Failed to execute action '{$action}': " . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get model class for app
     */
    private function getModelClass(string $app): ?string
    {
        return $this->resourceService->getModelClass($app);
    }

    /**
     * Get validation rules for app and operation
     */
    private function getValidationRules(string $app, string $operation): array
    {
        return $this->resourceService->getValidationRules($app, $operation);
    }

    /**
     * Prepare data for saving
     */
    private function prepareData(string $app, array $data, $existingResource = null): array
    {
        return $this->resourceService->prepareData(
            $app,
            $data,
            $this->request,
            (int) ($_SESSION['user_id'] ?? 1)
        );
    }

    /**
     * Handle custom actions for specific apps
     */
    private function handleCustomAction(string $app, string $action, $resource)
    {
        return $this->resourceService->handleCustomAction(
            $app,
            $action,
            $resource,
            (int) ($_SESSION['user_id'] ?? 0)
        );
    }
}


if (!\class_exists('DashboardApiController', false) && !\interface_exists('DashboardApiController', false) && !\trait_exists('DashboardApiController', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardApiController', 'DashboardApiController');
}
