<?php

namespace App\Controllers;


use App\Core\mvc\Controller;
use App\Core\security\CSRF;
use App\Core\security\Security;
use App\Core\services\DashboardBlogPostService;
use App\Core\services\DashboardBlogTaxonomyService;
use App\Core\services\DashboardContactMessageService;
use App\Core\services\DashboardGeoService;
use App\Core\services\DashboardIpTrackingService;
use App\Core\services\DashboardLanguageService;
use App\Core\services\DashboardMediaService;
use App\Core\services\DashboardNavigationService;
use App\Core\services\DashboardPageService;
use App\Core\services\DashboardRoleService;
use App\Core\services\DashboardSchemaService;
use App\Core\services\DashboardUserService;
use App\Core\view\Form;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\ContactMessage;
use App\Models\Continent;
use App\Models\Language;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;use BadMethodCallException;
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
 * Dashboard Controller
 * 
 * Database management dashboard
 */
class DashboardController extends Controller
{
    private DashboardUserService $dashboardUserService;
    private DashboardPageService $dashboardPageService;
    private DashboardNavigationService $dashboardNavigationService;
    private DashboardLanguageService $dashboardLanguageService;
    private DashboardGeoService $dashboardGeoService;
    private DashboardBlogTaxonomyService $dashboardBlogTaxonomyService;
    private DashboardBlogPostService $dashboardBlogPostService;
    private DashboardRoleService $dashboardRoleService;
    private DashboardIpTrackingService $dashboardIpTrackingService;
    private DashboardContactMessageService $dashboardContactMessageService;
    private DashboardSchemaService $dashboardSchemaService;
    private DashboardMediaService $dashboardMediaService;

    public function __construct(
        ?DashboardUserService $dashboardUserService = null,
        ?DashboardPageService $dashboardPageService = null,
        ?DashboardNavigationService $dashboardNavigationService = null,
        ?DashboardLanguageService $dashboardLanguageService = null,
        ?DashboardGeoService $dashboardGeoService = null,
        ?DashboardBlogTaxonomyService $dashboardBlogTaxonomyService = null,
        ?DashboardBlogPostService $dashboardBlogPostService = null,
        ?DashboardRoleService $dashboardRoleService = null,
        ?DashboardIpTrackingService $dashboardIpTrackingService = null,
        ?DashboardContactMessageService $dashboardContactMessageService = null,
        ?DashboardSchemaService $dashboardSchemaService = null,
        ?DashboardMediaService $dashboardMediaService = null
    )
    {
        parent::__construct();

        $this->dashboardUserService = $dashboardUserService ?? new DashboardUserService();
        $this->dashboardPageService = $dashboardPageService ?? new DashboardPageService();
        $this->dashboardNavigationService = $dashboardNavigationService ?? new DashboardNavigationService();
        $this->dashboardLanguageService = $dashboardLanguageService ?? new DashboardLanguageService();
        $this->dashboardGeoService = $dashboardGeoService ?? new DashboardGeoService();
        $this->dashboardBlogTaxonomyService = $dashboardBlogTaxonomyService ?? new DashboardBlogTaxonomyService();
        $this->dashboardBlogPostService = $dashboardBlogPostService ?? new DashboardBlogPostService($this->dashboardBlogTaxonomyService);
        $this->dashboardRoleService = $dashboardRoleService ?? new DashboardRoleService();
        $this->dashboardIpTrackingService = $dashboardIpTrackingService ?? new DashboardIpTrackingService();
        $this->dashboardContactMessageService = $dashboardContactMessageService ?? new DashboardContactMessageService();
        $this->dashboardSchemaService = $dashboardSchemaService ?? new DashboardSchemaService();
        $this->dashboardMediaService = $dashboardMediaService ?? new DashboardMediaService();
    }

    /**
     * Show dashboard home
     * 
     * Route: GET /dashboard
     */
    /**
     * Show dashboard home
     * 
     * Route: GET /dashboard
     */
    public function index(): void
    {
        // Dashboard home page - will be used for widgets/stats later
        $this->view('dashboard/dashboard-home');
    }

    /**
     * Show database management
     * 
     * Route: GET /dashboard/database
     */
    public function database(): void
    {
        $this->view(
            'dashboard/database-manager/index',
            $this->dashboardSchemaService->getDatabaseOverview()
        );
    }

    /**
     * Show table details
     * 
     * Route: GET /dashboard/tables/{table}
     */
    public function showTable(string $table): void
    {
        if (!$this->dashboardSchemaService->tableExists($table)) {
            $this->abort(404, 'Table not found');
        }

        $tableInfo = $this->dashboardSchemaService->getTableInfo($table);

        if ($this->wantsJson()) {
            $this->success($tableInfo);
        }

        $this->view('dashboard/database-manager/table', [
            'table' => $tableInfo
        ]);
    }

    /**
     * Show create table form
     * 
     * Route: GET /dashboard/tables/create
     */
    public function createTable(): void
    {
        $this->view('dashboard/database-manager/create-table');
    }

    /**
     * Store new table
     * 
     * Route: POST /dashboard/tables
     */
    public function storeTable(): void
    {
        // Validate input
        $validation = $this->validate([
            'table_name' => 'required|minLength:1|maxLength:64',
        ]);

        if ($validation !== true) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }

        $tableName = Security::sanitize($this->request->input('table_name'), 'slug');

        if ($this->dashboardSchemaService->tableExists($tableName)) {
            $errors = ['table_name' => ['Table already exists']];
            if ($this->wantsJson()) {
                $this->validationError($errors);
            }
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }

        // Create table with ID column by default
        try {
            $this->dashboardSchemaService->createTable($tableName);

            if ($this->wantsJson()) {
                $this->success(['table' => $tableName], 'Table created successfully', 201);
            }

            $this->redirect(route('dashboard.table', ['table' => $tableName]));
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to create table: ' . $e->getMessage(), null, 500);
            }

            $errors = ['table_name' => ['Failed to create table: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Show add column form
     * 
     * Route: GET /dashboard/tables/{table}/columns/create
     */
    public function createColumn(string $table): void
    {
        if (!$this->dashboardSchemaService->tableExists($table)) {
            $this->abort(404, 'Table not found');
        }

        $this->view('dashboard/database-manager/create-column', [
            'table' => $table
        ]);
    }

    /**
     * Store new column
     * 
     * Route: POST /dashboard/tables/{table}/columns
     */
    public function storeColumn(string $table): void
    {
        if (!$this->dashboardSchemaService->tableExists($table)) {
            $this->abort(404, 'Table not found');
        }

        // Validate input
        $validation = $this->validate([
            'column_name' => 'required|minLength:1|maxLength:64',
            'column_type' => 'required',
        ]);

        if ($validation !== true) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }

        $columnDefinition = $this->dashboardSchemaService->normalizeColumnDefinition([
            'column_name' => $this->request->input('column_name'),
            'column_type' => $this->request->input('column_type'),
            'length' => $this->request->input('length'),
            'nullable' => $this->request->has('nullable'),
            'default' => $this->request->input('default'),
            'unique' => $this->request->has('unique'),
        ]);

        try {
            $this->dashboardSchemaService->addColumn($table, $columnDefinition);

            if ($this->wantsJson()) {
                $this->success(['column' => $columnDefinition['name']], 'Column created successfully', 201);
            }

            $this->redirect(route('dashboard.table', ['table' => $table]));
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to create column: ' . $e->getMessage(), null, 500);
            }

            $errors = ['column_name' => ['Failed to create column: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Drop table
     * 
     * Route: DELETE /dashboard/tables/{table}
     */
    public function dropTable(string $table): void
    {
        if (!$this->dashboardSchemaService->tableExists($table)) {
            $this->abort(404, 'Table not found');
        }

        try {
            $this->dashboardSchemaService->dropTable($table);

            if ($this->wantsJson()) {
                $this->success(null, 'Table deleted successfully');
            }

            $this->redirect(route('dashboard.database'));
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to delete table: ' . $e->getMessage(), null, 500);
            }

            $this->redirectBack();
        }
    }

    /**
     * Drop column
     * 
     * Route: DELETE /dashboard/tables/{table}/columns/{column}
     */
    public function dropColumn(string $table, string $column): void
    {
        if (!$this->dashboardSchemaService->tableExists($table)) {
            $this->abort(404, 'Table not found');
        }

        try {
            $this->dashboardSchemaService->dropColumn($table, $column);

            if ($this->wantsJson()) {
                $this->success(null, 'Column deleted successfully');
            }

            $this->redirect(route('dashboard.table', ['table' => $table]));
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to delete column: ' . $e->getMessage(), null, 500);
            }

            $this->redirectBack();
        }
    }

    /**
     * Show users list
     * 
     * Route: GET /dashboard/users
     */
    public function users(): void
    {
        $page = max(1, (int) ($this->request->input('page', 1)));

        $this->view(
            'dashboard/user-manager/index',
            $this->dashboardUserService->buildUserListData($page)
        );
    }

    /**
     * Show create user form
     * 
     * Route: GET /dashboard/users/create
     */
    public function createUser(): void
    {
        $this->dashboardUserService->clearFormState();
        
        $this->view('dashboard/user-manager/create');
    }

    /**
     * Validate user uniqueness (email and username)
     * 
     * @param string $email Email to check
     * @param string $username Username to check
     * @param int|null $excludeId User ID to exclude from check (for updates)
     * @return array Array of validation errors (empty if valid)
     */
    private function validateUserUniqueness(string $email, string $username, ?int $excludeId = null): array
    {
        return $this->dashboardUserService->validateUniqueness($email, $username, $excludeId);
    }

    /**
     * Store new user
     * 
     * Route: POST /dashboard/users
     */
    public function storeUser(): void
    {
        // Check CSRF token
        if (!CSRF::verify()) {
            if ($this->wantsJson()) {
                $this->error('CSRF token verification failed', null, 403);
            }
            $this->abort(403, 'CSRF token verification failed');
        }
        
        // Validate input
        $validation = $this->validate([
            'username' => 'required|minLength:3|maxLength:30',
            'email' => 'required|email',
            'password' => 'required|minLength:8',
            'first_name' => 'maxLength:50',
            'last_name' => 'maxLength:50',
            // newsletter is optional checkbox - don't validate if not present
        ]);

        $validation = $this->dashboardUserService->validateCreateInput(
            $validation,
            $this->request->all()
        );

        if ($validation !== true) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
            return;
        }

        try {
            $user = $this->dashboardUserService->createUser(
                $this->request->all(),
                $this->request->files('avatar')
            );

            if ($this->wantsJson()) {
                $this->success($user->toArray(), 'User created successfully', 201);
            }

            // Redirect to edit page of newly created user
            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/users/{$user->id}/edit");
        } catch (Exception $e) {
            error_log('Store user error: ' . $e->getMessage());
            if ($this->wantsJson()) {
                $this->error('Failed to create user: ' . $e->getMessage(), null, 500);
            }
            
            $errors = ['general' => ['Failed to create user: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Show user details
     * 
     * Route: GET /dashboard/users/{id}
     */
    public function showUser(int $id): void
    {
        $user = User::find($id);
        
        if (!$user) {
            $this->abort(404, 'User not found');
        }

        $this->view('dashboard/user-manager/show', [
            'user' => $this->dashboardUserService->normalizeUserArray($user)
        ]);
    }

    /**
     * Show user edit form
     * 
     * Route: GET /dashboard/users/{id}/edit
     */
    public function editUser(int $id): void
    {
        error_log("=== editUser() CALLED ===");
        error_log("Received ID parameter: {$id} (type: " . gettype($id) . ")");
        error_log("Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
        
        $user = User::find($id);
        
        if (!$user) {
            error_log("User with ID {$id} not found");
            $this->abort(404, 'User not found');
        }
        
        error_log("Found user: ID={$user->id}, username={$user->username}");
        error_log("User data: " . json_encode($user->toArray()));

        $this->view(
            'dashboard/user-manager/edit',
            $this->dashboardUserService->buildEditFormData($user)
        );
    }

    /**
     * Update user
     * 
     * Route: POST /dashboard/users/{id}
     */
    public function updateUser(int $id): void
    {
        $user = User::find($id);
        
        if (!$user) {
            $this->abort(404, 'User not found');
        }

        // Validate input
        $validation = $this->validate([
            'username' => 'required|minLength:3|maxLength:30',
            'email' => 'required|email',
            'first_name' => 'maxLength:50',
            'last_name' => 'maxLength:50',
            // newsletter is optional checkbox - don't validate if not present
        ]);

        $validation = $this->dashboardUserService->validateUpdateInput(
            $validation,
            $this->request->all(),
            $id
        );
        
        if ($validation !== true) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
                return; // Exit early for JSON requests
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
            return;
        }

        try {
            $currentUser = isset($_SESSION['user_id']) ? User::find($_SESSION['user_id']) : null;
            $user = $this->dashboardUserService->updateUser(
                $user,
                $this->request->all(),
                $this->request->files('avatar'),
                $currentUser
            );

            if ($this->wantsJson()) {
                $this->success($user->toArray(), 'User updated successfully');
                return; // Exit early for JSON requests (success() already calls exit, but this is safer)
            }

            $this->redirect(route('dashboard.users'));
        } catch (Exception $e) {
            error_log('Update user error: ' . $e->getMessage());
            if ($this->wantsJson()) {
                $this->error('Failed to update user: ' . $e->getMessage(), null, 500);
            }
            
            $errors = ['general' => ['Failed to update user: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Handle avatar upload/delete for user
     */
    private function handleAvatarUpload(User $user): void
    {
        $errors = $this->dashboardUserService->applyAvatarChanges(
            $user,
            $this->request->has('delete_avatar') && $this->request->input('delete_avatar') == '1',
            $_FILES['avatar'] ?? null,
            'avatar'
        );

        if (!empty($errors)) {
            Form::flashErrors(['avatar' => $errors]);
        }
    }

    /**
     * Delete avatar file from filesystem
     */
    private function deleteAvatarFile(?string $avatarPath): void
    {
        $this->dashboardUserService->deleteAvatarFile($avatarPath);
    }

    /**
     * Delete user
     * 
     * Route: POST /dashboard/users/{id}/delete
     */
    public function deleteUser(int $id): void
    {
        $user = User::find($id);
        
        if (!$user) {
            $this->abort(404, 'User not found');
        }

        try {
            $this->dashboardUserService->deleteUser($user, (int) ($_SESSION['user_id'] ?? 0));
        } catch (InvalidArgumentException $e) {
            if ($this->wantsJson()) {
                $this->error($e->getMessage(), null, 403);
            }
            $this->abort(403, $e->getMessage());
        }

        if ($this->wantsJson()) {
            $this->success(null, 'User deleted successfully');
        }

        $this->redirect(route('dashboard.users'));
    }

    /**
     * Ban user
     * 
     * Route: POST /dashboard/users/{id}/ban
     */
    public function banUser(int $id): void
    {
        $user = User::find($id);
        
        if (!$user) {
            $this->abort(404, 'User not found');
        }

        try {
            $user = $this->dashboardUserService->banUser($user, (int) ($_SESSION['user_id'] ?? 0));
        } catch (InvalidArgumentException $e) {
            if ($this->wantsJson()) {
                $this->error($e->getMessage(), null, 403);
            }
            $this->abort(403, $e->getMessage());
        }

        if ($this->wantsJson()) {
            $this->success($user->toArray(), 'User banned successfully');
        }

        $this->redirect(route('dashboard.user.show', ['id' => $id]));
    }

    /**
     * Unban user
     * 
     * Route: POST /dashboard/users/{id}/unban
     */
    public function unbanUser(int $id): void
    {
        $user = User::find($id);
        
        if (!$user) {
            $this->abort(404, 'User not found');
        }

        $user = $this->dashboardUserService->unbanUser($user);

        if ($this->wantsJson()) {
            $this->success($user->toArray(), 'User unbanned successfully');
        }

        $this->redirect(route('dashboard.user.show', ['id' => $id]));
    }

    /**
     * Approve user
     * 
     * Route: POST /dashboard/users/{id}/approve
     */
    public function approveUser(int $id): void
    {
        $user = User::find($id);
        
        if (!$user) {
            $this->abort(404, 'User not found');
        }

        $user = $this->dashboardUserService->approveUser($user);

        if ($this->wantsJson()) {
            $this->success($user->toArray(), 'User approved successfully');
        }

        $this->redirect(route('dashboard.user.show', ['id' => $id]));
    }

    /**
     * Show pages list
     *
     * Route: GET /dashboard/pages
     */
    public function pages(): void
    {
        $this->view('dashboard/page-manager/index', [
            'pages' => $this->dashboardPageService->getPageList()
        ]);
    }

    /**
     * Show create page form
     *
     * Route: GET /dashboard/pages/create
     */
    public function createPage(): void
    {
        $this->view('dashboard/page-manager/create', $this->dashboardPageService->buildPageFormData());
    }

    /**
     * Store new page
     *
     * Route: POST /dashboard/pages
     */
    public function storePage(): void
    {
        // Validate input (application can be empty for custom pages)
        $validation = $this->validate([
            'title' => 'required|minLength:1|maxLength:255',
            'slug' => 'required|minLength:1|maxLength:255',
            'route' => 'required|minLength:1|maxLength:255',
            'is_active' => 'bool',
            'is_in_menu' => 'bool',
            'menu_order' => 'int',
        ]);

        $validation = $this->dashboardPageService->validatePageInput(
            $validation,
            $this->request->all()
        );
        
        if ($validation !== true && !empty($validation)) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
            return;
        }

        try {
            $page = $this->dashboardPageService->savePage(new Page(), $this->request->all());

            // Only return JSON if it's explicitly an AJAX request, not just Accept header
            if ($this->request->isAjax()) {
                $this->success(['page' => $page->toArray()], 'Page created successfully', 201);
                return; // Exit after JSON response
            }

            // Redirect to pages list
            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/pages");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to create page: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to create page: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Show edit page form
     *
     * Route: GET /dashboard/pages/{id}/edit
     */
    public function editPage(int $id): void
    {
        $page = Page::find($id);

        if (!$page) {
            $this->abort(404, 'Page not found');
        }

        $pageFormData = $this->dashboardPageService->buildPageFormData($id);

        $pageArray = $this->dashboardPageService->preparePageForEdit($page);
        
        error_log('DEBUG editPage - display_options type: ' . gettype($pageArray['display_options'] ?? 'not set'));
        error_log('DEBUG editPage - display_style set to: ' . ($pageArray['display_style'] ?? 'not set'));

        $this->view('dashboard/page-manager/edit', [
            'page' => $pageArray,
            'parentPages' => $pageFormData['parentPages'],
            'blogPosts' => $pageFormData['blogPosts'],
            'blogCategories' => $pageFormData['blogCategories'],
            'blogTags' => $pageFormData['blogTags'],
            'navigationMenus' => $pageFormData['navigationMenus'],
            'languages' => $pageFormData['languages'],
            'languagesData' => $pageFormData['languagesData'],
        ]);
    }

    /**
     * Update page
     *
     * Route: POST /dashboard/pages/{id}
     */
    public function updatePage(int $id): void
    {
        $page = Page::find($id);

        if (!$page) {
            $this->abort(404, 'Page not found');
        }

        // Get application first (before validation)
        $application = $this->request->input('application', '');
        
        // Validate input (application can be empty for custom pages)
        $validation = $this->validate([
            'title' => 'required|minLength:1|maxLength:255',
            'slug' => 'required|minLength:1|maxLength:255',
            'route' => 'required|minLength:1|maxLength:255',
            'is_active' => 'bool',
            'is_in_menu' => 'bool',
            'menu_order' => 'int',
        ]);

        $validation = $this->dashboardPageService->validatePageInput(
            $validation,
            $this->request->all(),
            $id
        );
        
        if ($validation !== true && !empty($validation)) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
            return;
        }

        try {
            $this->dashboardPageService->savePage($page, $this->request->all());

            // For updatePage, always redirect to dashboard after successful update
            // We don't return JSON for regular form submissions - only redirect
            // JSON responses are only for explicit AJAX requests (which we don't use for this form)
            
            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/pages");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to update page: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to update page: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Delete page
     *
     * Route: POST /dashboard/pages/{id}/delete
     */
    public function deletePage(int $id): void
    {
        $page = Page::find($id);

        if (!$page) {
            $this->abort(404, 'Page not found');
        }

        try {
            $this->dashboardPageService->deletePage($page);

            if ($this->wantsJson()) {
                $this->success(null, 'Page deleted successfully');
            }

            // Redirect to pages list
            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/pages");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to delete page: ' . $e->getMessage(), null, 500);
            }

            $this->abort(500, 'Failed to delete page: ' . $e->getMessage());
        }
    }

    // ========== Navigation Menu Management ==========

    /**
     * List all navigation menus
     * 
     * Route: GET /dashboard/navigation-menus
     */
    public function navigationMenus(): void
    {
        if (!class_exists('NavigationMenu')) {
            $this->abort(500, 'NavigationMenu model not found');
        }

        $this->view('dashboard/navigation-menu-manager/index', [
            'menus' => $this->dashboardNavigationService->getMenuList()
        ]);
    }

    /**
     * Show create navigation menu form
     * 
     * Route: GET /dashboard/navigation-menus/create
     */
    public function createNavigationMenu(): void
    {
        if (!class_exists('NavigationMenu')) {
            $this->abort(500, 'NavigationMenu model not found');
        }

        $this->view(
            'dashboard/navigation-menu-manager/create',
            $this->dashboardNavigationService->buildLanguageOptions()
        );
    }

    /**
     * Store new navigation menu
     * 
     * Route: POST /dashboard/navigation-menus
     */
    public function storeNavigationMenu(): void
    {
        if (!class_exists('NavigationMenu')) {
            $this->abort(500, 'NavigationMenu model not found');
        }

        $validation = $this->validate([
            'name' => 'required|minLength:1|maxLength:255',
            'position' => 'required|minLength:1|maxLength:50',
            'is_active' => 'bool',
            'menu_order' => 'int',
        ]);

        if ($validation !== true && !empty($validation)) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
            return;
        }

        try {
            $menu = $this->dashboardNavigationService->saveMenu(
                new NavigationMenu(),
                $this->request->all()
            );

            if ($this->wantsJson()) {
                $this->success(['menu' => $menu->toArray()], 'Navigation menu created successfully', 201);
                return;
            }

            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/navigation-menus");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to create navigation menu: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to create navigation menu: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Show edit navigation menu form
     * 
     * Route: GET /dashboard/navigation-menus/{id}/edit
     */
    public function editNavigationMenu(int $id): void
    {
        if (!class_exists('NavigationMenu')) {
            $this->abort(500, 'NavigationMenu model not found');
        }

        $menu = NavigationMenu::find($id);

        if (!$menu) {
            $this->abort(404, 'Navigation menu not found');
        }

        $this->view(
            'dashboard/navigation-menu-manager/edit',
            $this->dashboardNavigationService->buildEditFormData($menu)
        );
    }

    /**
     * Update navigation menu
     * 
     * Route: POST /dashboard/navigation-menus/{id}
     */
    public function updateNavigationMenu(int $id): void
    {
        if (!class_exists('NavigationMenu')) {
            $this->abort(500, 'NavigationMenu model not found');
        }

        $menu = NavigationMenu::find($id);

        if (!$menu) {
            $this->abort(404, 'Navigation menu not found');
        }

        $validation = $this->validate([
            'name' => 'required|minLength:1|maxLength:255',
            'position' => 'required|minLength:1|maxLength:50',
            'is_active' => 'bool',
            'menu_order' => 'int',
        ]);

        if ($validation !== true && !empty($validation)) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
            return;
        }

        try {
            $menu = $this->dashboardNavigationService->saveMenu($menu, $this->request->all());

            if ($this->wantsJson()) {
                $this->success(['menu' => $menu->toArray()], 'Navigation menu updated successfully');
                return;
            }

            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/navigation-menus");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to update navigation menu: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to update navigation menu: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Delete navigation menu
     * 
     * Route: POST /dashboard/navigation-menus/{id}/delete
     */
    public function deleteNavigationMenu(int $id): void
    {
        if (!class_exists('NavigationMenu')) {
            $this->abort(500, 'NavigationMenu model not found');
        }

        $menu = NavigationMenu::find($id);

        if (!$menu) {
            $this->abort(404, 'Navigation menu not found');
        }

        try {
            $this->dashboardNavigationService->deleteMenu($menu);

            if ($this->wantsJson()) {
                $this->success(null, 'Navigation menu deleted successfully');
            }

            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/navigation-menus");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to delete navigation menu: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to delete navigation menu: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            $this->redirectBack();
        }
    }

    /**
     * List all languages
     * 
     * Route: GET /dashboard/languages
     */
    public function languages(): void
    {
        if (!class_exists('Language')) {
            $this->abort(500, 'Language model not found');
        }

        $this->view('dashboard/language-manager/index', [
            'languages' => $this->dashboardLanguageService->getLanguageList()
        ]);
    }

    /**
     * Show create language form
     * 
     * Route: GET /dashboard/languages/create
     */
    public function createLanguage(): void
    {
        if (!class_exists('Language')) {
            $this->abort(500, 'Language model not found');
        }

        $this->view(
            'dashboard/language-manager/create',
            $this->dashboardLanguageService->buildGeoFormData()
        );
    }

    /**
     * Store new language
     * 
     * Route: POST /dashboard/languages
     */
    public function storeLanguage(): void
    {
        if (!class_exists('Language')) {
            $this->abort(500, 'Language model not found');
        }

        $validation = $this->validate([
            'code' => 'required|minLength:2|maxLength:10',
            'name' => 'required|minLength:1|maxLength:100',
            'native_name' => 'required|minLength:1|maxLength:100',
            'flag' => 'maxLength:10',
            'is_active' => 'bool',
            'is_default' => 'bool',
            'sort_order' => 'int',
        ]);

        $validation = $this->dashboardLanguageService->validateLanguageInput(
            $validation,
            $this->request->all()
        );

        if ($validation !== true && !empty($validation)) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
            return;
        }

        try {
            $language = $this->dashboardLanguageService->saveLanguage(
                new Language(),
                $this->request->all()
            );

            if ($this->wantsJson()) {
                $this->success(['language' => $language->toArray()], 'Language created successfully', 201);
                return;
            }

            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/languages");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to create language: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to create language: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Show edit language form
     * 
     * Route: GET /dashboard/languages/{id}/edit
     */
    public function editLanguage(int $id): void
    {
        if (!class_exists('Language')) {
            $this->abort(500, 'Language model not found');
        }

        $language = Language::find($id);

        if (!$language) {
            $this->abort(404, 'Language not found');
        }

        $this->view(
            'dashboard/language-manager/edit',
            $this->dashboardLanguageService->buildEditFormData($language)
        );
    }

    /**
     * Update language
     * 
     * Route: POST /dashboard/languages/{id}
     */
    public function updateLanguage(int $id): void
    {
        if (!class_exists('Language')) {
            $this->abort(500, 'Language model not found');
        }

        $language = Language::find($id);

        if (!$language) {
            $this->abort(404, 'Language not found');
        }

        $validation = $this->validate([
            'code' => 'required|minLength:2|maxLength:10',
            'name' => 'required|minLength:1|maxLength:100',
            'native_name' => 'required|minLength:1|maxLength:100',
            'flag' => 'maxLength:10',
            'is_active' => 'bool',
            'is_default' => 'bool',
            'sort_order' => 'int',
        ]);

        $validation = $this->dashboardLanguageService->validateLanguageInput(
            $validation,
            $this->request->all(),
            $id
        );

        if ($validation !== true && !empty($validation)) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
            return;
        }

        try {
            $language = $this->dashboardLanguageService->saveLanguage(
                $language,
                $this->request->all()
            );

            if ($this->wantsJson()) {
                $this->success(['language' => $language->toArray()], 'Language updated successfully');
                return;
            }

            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/languages");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to update language: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to update language: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Delete language
     * 
     * Route: POST /dashboard/languages/{id}/delete
     */
    public function deleteLanguage(int $id): void
    {
        if (!class_exists('Language')) {
            $this->abort(500, 'Language model not found');
        }

        $language = Language::find($id);

        if (!$language) {
            $this->abort(404, 'Language not found');
        }

        try {
            $this->dashboardLanguageService->deleteLanguage($language);

            if ($this->wantsJson()) {
                $this->success(null, 'Language deleted successfully');
            }

            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/languages");
        } catch (InvalidArgumentException $e) {
            if ($this->wantsJson()) {
                $this->validationError(['general' => [$e->getMessage()]]);
            }

            Form::flashErrors(['general' => [$e->getMessage()]]);
            $this->redirectBack();
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to delete language: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to delete language: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            $this->redirectBack();
        }
    }

    /**
     * Set default language
     * 
     * Route: POST /dashboard/languages/{id}/set-default
     */
    public function setDefaultLanguage(int $id): void
    {
        if (!class_exists('Language')) {
            $this->abort(500, 'Language model not found');
        }

        $language = Language::find($id);

        if (!$language) {
            $this->abort(404, 'Language not found');
        }

        try {
            $language = $this->dashboardLanguageService->setDefaultLanguage($language);

            if ($this->wantsJson()) {
                $this->success(['language' => $language->toArray()], 'Default language updated successfully');
            }

            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/languages");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to set default language: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to set default language: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            $this->redirectBack();
        }
    }

    // ========== Continents Management ==========

    /**
     * List all continents
     * 
     * Route: GET /dashboard/continents
     */
    public function continents(): void
    {
        if (!class_exists('Continent')) {
            $this->abort(500, 'Continent model not found');
        }

        $this->view('dashboard/world-manager/continents/index');
    }

    /**
     * Show create continent form
     * 
     * Route: GET /dashboard/continents/create
     */
    public function createContinent(): void
    {
        if (!class_exists('Continent')) {
            $this->abort(500, 'Continent model not found');
        }

        $this->view('dashboard/world-manager/continents/create');
    }

    /**
     * Store new continent
     * 
     * Route: POST /dashboard/continents
     */
    public function storeContinent(): void
    {
        if (!class_exists('Continent')) {
            $this->abort(500, 'Continent model not found');
        }

        $validation = $this->validate([
            'code' => 'required|minLength:2|maxLength:10',
            'name' => 'required|minLength:1|maxLength:100',
            'native_name' => 'maxLength:100',
            'is_active' => 'bool',
            'sort_order' => 'int',
        ]);

        $validation = $this->dashboardGeoService->validateContinentInput(
            $validation,
            $this->request->all()
        );

        if ($validation !== true && !empty($validation)) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
            return;
        }

        try {
            $continent = $this->dashboardGeoService->saveContinent(
                new Continent(),
                $this->request->all()
            );

            if ($this->wantsJson()) {
                $this->success(['continent' => $continent->toArray()], 'Continent created successfully', 201);
                return;
            }

            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/continents");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to create continent: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to create continent: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Show edit continent form
     * 
     * Route: GET /dashboard/continents/{id}/edit
     */
    public function editContinent(int $id): void
    {
        if (!class_exists('Continent')) {
            $this->abort(500, 'Continent model not found');
        }

        $continent = Continent::find($id);

        if (!$continent) {
            $this->abort(404, 'Continent not found');
        }

        $this->view(
            'dashboard/world-manager/continents/edit',
            $this->dashboardGeoService->buildContinentEditData($continent)
        );
    }

    /**
     * Update continent
     * 
     * Route: POST /dashboard/continents/{id}
     */
    public function updateContinent(int $id): void
    {
        if (!class_exists('Continent')) {
            $this->abort(500, 'Continent model not found');
        }

        $continent = Continent::find($id);

        if (!$continent) {
            $this->abort(404, 'Continent not found');
        }

        $validation = $this->validate([
            'code' => 'required|minLength:2|maxLength:10',
            'name' => 'required|minLength:1|maxLength:100',
            'native_name' => 'maxLength:100',
            'is_active' => 'bool',
            'sort_order' => 'int',
        ]);

        $validation = $this->dashboardGeoService->validateContinentInput(
            $validation,
            $this->request->all(),
            $id
        );

        if ($validation !== true && !empty($validation)) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
            return;
        }

        try {
            $continent = $this->dashboardGeoService->saveContinent(
                $continent,
                $this->request->all()
            );

            if ($this->wantsJson()) {
                $this->success(['continent' => $continent->toArray()], 'Continent updated successfully');
            }

            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/continents");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to update continent: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to update continent: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Delete continent
     * 
     * Route: POST /dashboard/continents/{id}/delete
     */
    public function deleteContinent(int $id): void
    {
        if (!class_exists('Continent')) {
            $this->abort(500, 'Continent model not found');
        }

        $continent = Continent::find($id);

        if (!$continent) {
            $this->abort(404, 'Continent not found');
        }

        try {
            $this->dashboardGeoService->deleteContinent($continent);

            if ($this->wantsJson()) {
                $this->success(null, 'Continent deleted successfully');
            }

            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/continents");
        } catch (InvalidArgumentException $e) {
            if ($this->wantsJson()) {
                $this->error($e->getMessage(), null, 400);
            }

            Form::flashErrors(['general' => [$e->getMessage()]]);
            $this->redirectBack();
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to delete continent: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to delete continent: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            $this->redirectBack();
        }
    }

    // ========== Regions Management ==========

    /**
     * List all regions
     * 
     * Route: GET /dashboard/regions
     */
    public function regions(): void
    {
        if (!class_exists('Region')) {
            $this->abort(500, 'Region model not found');
        }

        $this->view('dashboard/world-manager/regions/index');
    }

    /**
     * Show create region form
     * 
     * Route: GET /dashboard/regions/create
     */
    public function createRegion(): void
    {
        if (!class_exists('Region')) {
            $this->abort(500, 'Region model not found');
        }

        $this->view('dashboard/world-manager/regions/create', [
            'continents' => $this->dashboardGeoService->buildContinentOptions()
        ]);
    }

    /**
     * Store new region
     * 
     * Route: POST /dashboard/regions
     */
    public function storeRegion(): void
    {
        if (!class_exists('Region')) {
            $this->abort(500, 'Region model not found');
        }

        $validation = $this->validate([
            'continent_id' => 'required|int',
            'name' => 'required|minLength:1|maxLength:100',
            'code' => 'maxLength:20',
            'native_name' => 'maxLength:100',
            'description' => 'maxLength:500',
            'is_active' => 'bool',
            'sort_order' => 'int',
        ]);

        $validation = $this->dashboardGeoService->validateRegionInput(
            $validation,
            $this->request->all()
        );

        if ($validation !== true && !empty($validation)) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
            return;
        }

        try {
            $region = $this->dashboardGeoService->saveRegion(
                new Region(),
                $this->request->all()
            );

            if ($this->wantsJson()) {
                $this->success(['region' => $region->toArray()], 'Region created successfully', 201);
                return;
            }

            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/regions");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to create region: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to create region: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Show edit region form
     * 
     * Route: GET /dashboard/regions/{id}/edit
     */
    public function editRegion(int $id): void
    {
        if (!class_exists('Region')) {
            $this->abort(500, 'Region model not found');
        }

        $region = Region::find($id);

        if (!$region) {
            $this->abort(404, 'Region not found');
        }

        $this->view(
            'dashboard/world-manager/regions/edit',
            $this->dashboardGeoService->buildRegionEditData($region)
        );
    }

    /**
     * Update region
     * 
     * Route: POST /dashboard/regions/{id}
     */
    public function updateRegion(int $id): void
    {
        if (!class_exists('Region')) {
            $this->abort(500, 'Region model not found');
        }

        $region = Region::find($id);

        if (!$region) {
            $this->abort(404, 'Region not found');
        }

        $validation = $this->validate([
            'continent_id' => 'required|int',
            'name' => 'required|minLength:1|maxLength:100',
            'code' => 'maxLength:20',
            'native_name' => 'maxLength:100',
            'description' => 'maxLength:500',
            'is_active' => 'bool',
            'sort_order' => 'int',
        ]);

        $validation = $this->dashboardGeoService->validateRegionInput(
            $validation,
            $this->request->all(),
            $id
        );

        if ($validation !== true && !empty($validation)) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
            return;
        }

        try {
            $region = $this->dashboardGeoService->saveRegion(
                $region,
                $this->request->all()
            );

            if ($this->wantsJson()) {
                $this->success(['region' => $region->toArray()], 'Region updated successfully');
            }

            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/regions");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to update region: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to update region: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Delete region
     * 
     * Route: POST /dashboard/regions/{id}/delete
     */
    public function deleteRegion(int $id): void
    {
        if (!class_exists('Region')) {
            $this->abort(500, 'Region model not found');
        }

        $region = Region::find($id);

        if (!$region) {
            $this->abort(404, 'Region not found');
        }

        try {
            $this->dashboardGeoService->deleteRegion($region);

            if ($this->wantsJson()) {
                $this->success(null, 'Region deleted successfully');
            }

            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/regions");
        } catch (InvalidArgumentException $e) {
            if ($this->wantsJson()) {
                $this->error($e->getMessage(), null, 400);
            }

            Form::flashErrors(['general' => [$e->getMessage()]]);
            $this->redirectBack();
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to delete region: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to delete region: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            $this->redirectBack();
        }
    }

    // ========== Blog Post Management ==========

    /**
     * List all blog posts
     * 
     * Route: GET /dashboard/blog/posts
     */
    public function blogPosts(): void
    {
        $this->view('dashboard/blog-manager/posts/index', [
            'posts' => $this->dashboardBlogPostService->getPostList()
        ]);
    }

    /**
     * Show create blog post form
     * 
     * Route: GET /dashboard/blog/posts/create
     */
    public function createBlogPost(): void
    {
        $this->view(
            'dashboard/blog-manager/posts/create',
            $this->dashboardBlogPostService->buildCreateFormData()
        );
    }

    /**
     * Store new blog post
     * 
     * Route: POST /dashboard/blog/posts
     */
    public function storeBlogPost(): void
    {
        // Validate input
        $validation = $this->validate([
            'title' => 'required|minLength:1|maxLength:255',
            'slug' => 'required|minLength:1|maxLength:255',
            'content' => 'required',
            'status' => 'required',
        ]);

        $validation = $this->dashboardBlogPostService->validatePostInput(
            $validation,
            $this->request->all()
        );

        if ($validation !== true) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
            return;
        }

        try {
            $post = $this->dashboardBlogPostService->savePost(
                new BlogPost(),
                $this->request->all(),
                (int) ($_SESSION['user_id'] ?? 1)
            );

            if ($this->wantsJson()) {
                $this->success(['post' => $post->toArray()], 'Blog post created successfully', 201);
            }

            // Redirect to posts list
            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/blog/posts");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to create blog post: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to create blog post: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Preview blog post
     * 
     * Route: GET /dashboard/blog/posts/{id}/preview
     */
    public function previewBlogPost(int $id): void
    {
        $post = BlogPost::find($id);

        if (!$post) {
            $this->abort(404, 'Blog post not found');
        }

        global $router;
        $lang = $router->lang ?? 'sr';

        $this->view(
            'blog/single',
            $this->dashboardBlogPostService->buildPreviewData($post, $lang)
        );
    }

    /**
     * Show edit blog post form
     * 
     * Route: GET /dashboard/blog/posts/{id}/edit
     */
    public function editBlogPost(int $id): void
    {
        $post = BlogPost::find($id);

        if (!$post) {
            $this->abort(404, 'Blog post not found');
        }

        $this->view(
            'dashboard/blog-manager/posts/edit',
            $this->dashboardBlogPostService->buildEditFormData($post)
        );
    }

    /**
     * Update blog post
     * 
     * Route: POST /dashboard/blog/posts/{id}
     */
    public function updateBlogPost(int $id): void
    {
        $post = BlogPost::find($id);

        if (!$post) {
            $this->abort(404, 'Blog post not found');
        }

        // Validate input
        $validation = $this->validate([
            'title' => 'required|minLength:1|maxLength:255',
            'slug' => 'required|minLength:1|maxLength:255',
            'content' => 'required',
            'status' => 'required',
        ]);

        $validation = $this->dashboardBlogPostService->validatePostInput(
            $validation,
            $this->request->all(),
            $id
        );

        if ($validation !== true) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
            return;
        }

        try {
            $post = $this->dashboardBlogPostService->savePost(
                $post,
                $this->request->all(),
                (int) ($_SESSION['user_id'] ?? 1)
            );

            if ($this->wantsJson()) {
                $this->success(['post' => $post->toArray()], 'Blog post updated successfully');
            }

            // Redirect back to edit page to see the updated post
            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/blog/posts/{$id}/edit");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to update blog post: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to update blog post: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Delete blog post
     * 
     * Route: POST /dashboard/blog/posts/{id}/delete
     */
    public function deleteBlogPost(int $id): void
    {
        $post = BlogPost::find($id);

        if (!$post) {
            $this->abort(404, 'Blog post not found');
        }

        try {
            $this->dashboardBlogPostService->deletePost($post);

            if ($this->wantsJson()) {
                $this->success(null, 'Blog post deleted successfully');
            }

            // Redirect to posts list
            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/blog/posts");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to delete blog post: ' . $e->getMessage(), null, 500);
            }

            $this->abort(500, 'Failed to delete blog post: ' . $e->getMessage());
        }
    }

    /**
     * Upload blog image
     * 
     * Route: POST /dashboard/blog/upload-image
     */
    public function uploadBlogImage(): void
    {
        // Verify CSRF token
        if (!CSRF::verify()) {
            if ($this->wantsJson()) {
                $this->error('CSRF token verification failed', null, 403);
            }
            $this->abort(403, 'CSRF token verification failed');
        }

        $uploadResult = $this->dashboardMediaService->uploadTinyMceImage($_FILES['file'] ?? null);
        if (empty($uploadResult['ok'])) {
            if ($this->wantsJson()) {
                $this->error($uploadResult['error'] ?? 'Upload failed', null, $uploadResult['status'] ?? 400);
            }
            $this->abort($uploadResult['status'] ?? 400, $uploadResult['error'] ?? 'Upload failed');
        }

        // Return JSON response for TinyMCE
        if ($this->wantsJson()) {
            $this->success([
                'location' => $uploadResult['payload']['location']
            ], 'Image uploaded successfully', 201);
        } else {
            // For non-JSON requests, return TinyMCE-compatible response
            header('Content-Type: application/json');
            echo json_encode([
                'location' => $uploadResult['payload']['location']
            ]);
            exit;
        }
    }

    /**
     * Serve blog image
     * 
     * Route: GET /storage/uploads/blog/{filename}
     */
    public function serveBlogImage(string $filename): void
    {
        $image = $this->dashboardMediaService->getServedBlogImage($filename);
        if (empty($image['ok'])) {
            $this->abort($image['status'] ?? 404, $image['error'] ?? 'Image not found');
        }
        
        header('Content-Type: ' . $image['contentType']);
        header('Content-Length: ' . $image['contentLength']);
        header('Cache-Control: public, max-age=31536000');
        
        readfile($image['path']);
        exit;
    }

    /**
     * Upload featured image
     * 
     * Route: POST /dashboard/blog/upload-featured-image
     */
    public function uploadFeaturedImage(): void
    {
        // This is an AJAX endpoint - always return JSON
        // Verify CSRF token
        if (!CSRF::verify()) {
            $this->error('CSRF token verification failed', null, 403);
            return;
        }

        $result = $this->dashboardMediaService->uploadFeaturedImage('file');
        if (empty($result['ok'])) {
            $this->error(
                $result['error'] ?? 'Upload failed',
                $result['errors'] ?? null,
                $result['status'] ?? 400
            );
            return;
        }

        $this->success($result['payload'], 'Image uploaded successfully', 201);
    }

    // ========== Blog Category Management ==========

    /**
     * List all blog categories
     * 
     * Route: GET /dashboard/blog/categories
     */
    public function blogCategories(): void
    {
        $this->view('dashboard/blog-manager/categories/index', [
            'categories' => $this->dashboardBlogTaxonomyService->getCategoryList()
        ]);
    }

    /**
     * Show create blog category form
     * 
     * Route: GET /dashboard/blog/categories/create
     */
    public function createBlogCategory(): void
    {
        $this->view(
            'dashboard/blog-manager/categories/create',
            $this->dashboardBlogTaxonomyService->buildCategoryFormData()
        );
    }

    /**
     * Store new blog category
     * 
     * Route: POST /dashboard/blog/categories
     */
    public function storeBlogCategory(): void
    {
        // Validate input
        $validation = $this->validate([
            'name' => 'required|minLength:1|maxLength:255',
            'slug' => 'required|minLength:1|maxLength:255',
        ]);

        $validation = $this->dashboardBlogTaxonomyService->validateCategoryInput(
            $validation,
            $this->request->all()
        );

        if ($validation !== true) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
            return;
        }

        try {
            $category = $this->dashboardBlogTaxonomyService->saveCategory(
                new BlogCategory(),
                $this->request->all()
            );

            if ($this->wantsJson()) {
                $this->success(['category' => $category->toArray()], 'Blog category created successfully', 201);
            }

            // Redirect to categories list
            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/blog/categories");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to create blog category: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to create blog category: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Preview blog category
     * 
     * Route: GET /dashboard/blog/categories/{id}/preview
     */
    public function previewBlogCategory(int $id): void
    {
        $category = BlogCategory::find($id);

        if (!$category) {
            $this->abort(404, 'Blog category not found');
        }

        global $router;
        $lang = $router->lang ?? 'sr';

        $this->view(
            'blog/category',
            $this->dashboardBlogTaxonomyService->buildCategoryPreviewData($category, $lang)
        );
    }

    /**
     * Show edit blog category form
     * 
     * Route: GET /dashboard/blog/categories/{id}/edit
     */
    public function editBlogCategory(int $id): void
    {
        $category = BlogCategory::find($id);

        if (!$category) {
            $this->abort(404, 'Blog category not found');
        }

        $this->view(
            'dashboard/blog-manager/categories/edit',
            $this->dashboardBlogTaxonomyService->buildCategoryEditFormData($category)
        );
    }

    /**
     * Update blog category
     * 
     * Route: POST /dashboard/blog/categories/{id}
     */
    public function updateBlogCategory(int $id): void
    {
        $category = BlogCategory::find($id);

        if (!$category) {
            $this->abort(404, 'Blog category not found');
        }

        // Validate input
        $validation = $this->validate([
            'name' => 'required|minLength:1|maxLength:255',
            'slug' => 'required|minLength:1|maxLength:255',
        ]);

        $validation = $this->dashboardBlogTaxonomyService->validateCategoryInput(
            $validation,
            $this->request->all(),
            $category
        );

        if ($validation !== true) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
            return;
        }

        try {
            $category = $this->dashboardBlogTaxonomyService->saveCategory(
                $category,
                $this->request->all()
            );

            if ($this->wantsJson()) {
                $this->success(['category' => $category->toArray()], 'Blog category updated successfully');
            }

            // Redirect to categories list
            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/blog/categories");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to update blog category: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to update blog category: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Delete blog category
     * 
     * Route: POST /dashboard/blog/categories/{id}/delete
     */
    public function deleteBlogCategory(int $id): void
    {
        $category = BlogCategory::find($id);

        if (!$category) {
            $this->abort(404, 'Blog category not found');
        }

        try {
            $this->dashboardBlogTaxonomyService->deleteCategory($category);

            if ($this->wantsJson()) {
                $this->success(null, 'Blog category deleted successfully');
            }

            // Redirect to categories list
            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/blog/categories");
        } catch (InvalidArgumentException $e) {
            if ($this->wantsJson()) {
                $this->validationError(['general' => [$e->getMessage()]]);
            }
            Form::flashErrors(['general' => [$e->getMessage()]]);
            $this->redirectBack();
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to delete blog category: ' . $e->getMessage(), null, 500);
            }

            $this->abort(500, 'Failed to delete blog category: ' . $e->getMessage());
        }
    }

    // ========== Blog Tag Management ==========

    /**
     * List all blog tags
     * 
     * Route: GET /dashboard/blog/tags
     */
    public function blogTags(): void
    {
        $this->view('dashboard/blog-manager/tags/index', [
            'tags' => $this->dashboardBlogTaxonomyService->getTagList()
        ]);
    }

    /**
     * Show create blog tag form
     * 
     * Route: GET /dashboard/blog/tags/create
     */
    public function createBlogTag(): void
    {
        $this->view(
            'dashboard/blog-manager/tags/create',
            $this->dashboardBlogTaxonomyService->buildTagFormData()
        );
    }

    /**
     * Store new blog tag
     * 
     * Route: POST /dashboard/blog/tags
     */
    public function storeBlogTag(): void
    {
        // Validate input
        $validation = $this->validate([
            'name' => 'required|minLength:1|maxLength:100',
            'slug' => 'required|minLength:1|maxLength:100',
        ]);

        $validation = $this->dashboardBlogTaxonomyService->validateTagInput(
            $validation,
            $this->request->all()
        );

        if ($validation !== true) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
            return;
        }

        try {
            $tag = $this->dashboardBlogTaxonomyService->saveTag(
                new BlogTag(),
                $this->request->all()
            );

            if ($this->wantsJson()) {
                $this->success(['tag' => $tag->toArray()], 'Blog tag created successfully', 201);
            }

            // Redirect to tags list
            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/blog/tags");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to create blog tag: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to create blog tag: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Show edit blog tag form
     * 
     * Route: GET /dashboard/blog/tags/{id}/edit
     */
    public function editBlogTag(int $id): void
    {
        $tag = BlogTag::find($id);

        if (!$tag) {
            $this->abort(404, 'Blog tag not found');
        }

        $this->view(
            'dashboard/blog-manager/tags/edit',
            $this->dashboardBlogTaxonomyService->buildTagEditFormData($tag)
        );
    }

    /**
     * Update blog tag
     * 
     * Route: POST /dashboard/blog/tags/{id}
     */
    public function updateBlogTag(int $id): void
    {
        $tag = BlogTag::find($id);

        if (!$tag) {
            $this->abort(404, 'Blog tag not found');
        }

        // Validate input
        $validation = $this->validate([
            'name' => 'required|minLength:1|maxLength:100',
            'slug' => 'required|minLength:1|maxLength:100',
        ]);

        $validation = $this->dashboardBlogTaxonomyService->validateTagInput(
            $validation,
            $this->request->all(),
            $id
        );

        if ($validation !== true) {
            if ($this->wantsJson()) {
                $this->validationError($validation);
            }
            Form::flashErrors($validation);
            Form::flashOld($this->request->all());
            $this->redirectBack();
            return;
        }

        try {
            $tag = $this->dashboardBlogTaxonomyService->saveTag(
                $tag,
                $this->request->all()
            );

            if ($this->wantsJson()) {
                $this->success(['tag' => $tag->toArray()], 'Blog tag updated successfully');
            }

            // Redirect to tags list
            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/blog/tags");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to update blog tag: ' . $e->getMessage(), null, 500);
            }

            $errors = ['general' => ['Failed to update blog tag: ' . $e->getMessage()]];
            Form::flashErrors($errors);
            Form::flashOld($this->request->all());
            $this->redirectBack();
        }
    }

    /**
     * Delete blog tag
     * 
     * Route: POST /dashboard/blog/tags/{id}/delete
     */
    public function deleteBlogTag(int $id): void
    {
        $tag = BlogTag::find($id);

        if (!$tag) {
            $this->abort(404, 'Blog tag not found');
        }

        try {
            $this->dashboardBlogTaxonomyService->deleteTag($tag);

            if ($this->wantsJson()) {
                $this->success(null, 'Blog tag deleted successfully');
            }

            // Redirect to tags list
            global $router;
            $lang = $router->lang ?? 'sr';
            $this->redirect("/{$lang}/dashboard/blog/tags");
        } catch (Exception $e) {
            if ($this->wantsJson()) {
                $this->error('Failed to delete blog tag: ' . $e->getMessage(), null, 500);
            }

            $this->abort(500, 'Failed to delete blog tag: ' . $e->getMessage());
        }
    }

    // ========== Roles Management ==========

    /**
     * List all roles
     */
    public function roles(): void
    {
        $this->view('dashboard/user-manager/roles/index', [
            'roles' => $this->dashboardRoleService->getRoleList()
        ]);
    }

    /**
     * Show create role form
     */
    public function createRole(): void
    {
        $this->view('dashboard/user-manager/roles/create', [
            'permissions' => $this->dashboardRoleService->getPermissionGroups()
        ]);
    }

    /**
     * Store new role
     */
    public function storeRole(): void
    {
        $data = $this->request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100'],
            'description' => ['string', 'max:500'],
            'priority' => ['int', 'min:0', 'max:1000'],
            'permissions' => ['array']
        ]);

        $data = $this->dashboardRoleService->normalizeRoleData($data);

        $errors = $this->dashboardRoleService->validateRoleData($data);
        if (!empty($errors)) {
            Form::redirectBack($errors, $data);
        }

        try {
            $this->dashboardRoleService->createRole($data);

            $this->redirect('/dashboard/users/roles');
        } catch (Exception $e) {
            Form::redirectBack(['error' => ['Failed to create role: ' . $e->getMessage()]], $data);
        }
    }

    /**
     * Show edit role form
     */
    public function editRole(int $id): void
    {
        $role = Role::find($id);
        
        if (!$role) {
            $this->abort(404, 'Role not found');
        }

        $this->view(
            'dashboard/user-manager/roles/edit',
            $this->dashboardRoleService->buildEditFormData($role)
        );
    }

    /**
     * Update role
     */
    public function updateRole(int $id): void
    {
        $role = Role::find($id);
        
        if (!$role) {
            $this->abort(404, 'Role not found');
        }

        $data = $this->request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100'],
            'description' => ['string', 'max:500'],
            'priority' => ['int', 'min:0', 'max:1000'],
            'permissions' => ['array']
        ]);

        $data = $this->dashboardRoleService->normalizeRoleData($data);

        $errors = $this->dashboardRoleService->validateRoleData($data, $role);
        if (!empty($errors)) {
            Form::redirectBack($errors, $data);
        }

        try {
            $this->dashboardRoleService->updateRole($role, $data);

            $this->redirect('/dashboard/users/roles');
        } catch (Exception $e) {
            Form::redirectBack(['error' => ['Failed to update role: ' . $e->getMessage()]], $data);
        }
    }

    /**
     * Delete role
     */
    public function deleteRole(int $id): void
    {
        $role = Role::find($id);
        
        if (!$role) {
            $this->abort(404, 'Role not found');
        }

        try {
            $this->dashboardRoleService->deleteRole($role);
            
            $this->redirect('/dashboard/users/roles');
        } catch (InvalidArgumentException $e) {
            $this->redirectBack(['error' => [$e->getMessage()]]);
        } catch (Exception $e) {
            $this->abort(500, 'Failed to delete role: ' . $e->getMessage());
        }
    }

    // ========== Permissions Management ==========

    /**
     * List all permissions
     */
    public function permissions(): void
    {
        $this->view('dashboard/user-manager/permissions/index', [
            'permissions' => $this->dashboardRoleService->getPermissionGroups()
        ]);
    }

    // ========== IP Tracking ==========

    /**
     * Show IP tracking dashboard
     * 
     * Route: GET /dashboard/ip-tracking
     */
    public function ipTracking(): void
    {
        $page = max(1, (int) ($this->request->input('page', 1)));
        $ipFilter = (string) $this->request->input('ip', '');

        $this->view(
            'dashboard/ip-tracking/index',
            $this->dashboardIpTrackingService->buildDashboardData($page, $ipFilter)
        );
    }

    // ========== Contact Messages Management ==========

    /**
     * List all contact messages
     * 
     * Route: GET /dashboard/contact-messages
     */
    public function contactMessages(): void
    {
        $page = max(1, (int) ($this->request->query('page', 1)));

        $this->view(
            'dashboard/contact-messages/index',
            $this->dashboardContactMessageService->buildMessageListData($page)
        );
    }

    /**
     * Show single contact message
     * 
     * Route: GET /dashboard/contact-messages/{id}
     */
    public function showContactMessage(int $id): void
    {
        $message = ContactMessage::find($id);
        
        if (!$message) {
            $this->abort(404, 'Contact message not found');
        }

        $this->view(
            'dashboard/contact-messages/show',
            $this->dashboardContactMessageService->buildShowData($message)
        );
    }

    /**
     * Mark message as read
     * 
     * Route: POST /dashboard/contact-messages/{id}/read
     */
    public function markContactMessageRead(int $id): void
    {
        // Check CSRF token
        if (!CSRF::verify()) {
            if ($this->wantsJson()) {
                $this->error('CSRF token verification failed', null, 403);
            }
            $this->abort(403, 'CSRF token verification failed');
        }

        $message = ContactMessage::find($id);
        
        if (!$message) {
            $this->abort(404, 'Contact message not found');
        }

        $this->dashboardContactMessageService->markAsRead($message);

        if ($this->wantsJson()) {
            $this->success(['message' => 'Message marked as read']);
        }

        global $router;
        $lang = $router->lang ?? 'sr';
        $this->redirect("/{$lang}/dashboard/contact-messages/{$id}");
    }

    /**
     * Mark message as replied
     * 
     * Route: POST /dashboard/contact-messages/{id}/replied
     */
    public function markContactMessageReplied(int $id): void
    {
        // Check CSRF token
        if (!CSRF::verify()) {
            if ($this->wantsJson()) {
                $this->error('CSRF token verification failed', null, 403);
            }
            $this->abort(403, 'CSRF token verification failed');
        }

        $message = ContactMessage::find($id);
        
        if (!$message) {
            $this->abort(404, 'Contact message not found');
        }

        $this->dashboardContactMessageService->markAsReplied($message);

        if ($this->wantsJson()) {
            $this->success(['message' => 'Message marked as replied']);
        }

        global $router;
        $lang = $router->lang ?? 'sr';
        $this->redirect("/{$lang}/dashboard/contact-messages/{$id}");
    }

    /**
     * Delete contact message
     * 
     * Route: POST /dashboard/contact-messages/{id}/delete
     */
    public function deleteContactMessage(int $id): void
    {
        // Check CSRF token
        if (!CSRF::verify()) {
            if ($this->wantsJson()) {
                $this->error('CSRF token verification failed', null, 403);
            }
            $this->abort(403, 'CSRF token verification failed');
        }

        $message = ContactMessage::find($id);
        
        if (!$message) {
            $this->abort(404, 'Contact message not found');
        }

        $this->dashboardContactMessageService->deleteMessage($message);

        if ($this->wantsJson()) {
            $this->success(['message' => 'Message deleted']);
        }

        global $router;
        $lang = $router->lang ?? 'sr';
        $this->redirect("/{$lang}/dashboard/contact-messages");
    }
}


if (!\class_exists('DashboardController', false) && !\interface_exists('DashboardController', false) && !\trait_exists('DashboardController', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardController', 'DashboardController');
}
