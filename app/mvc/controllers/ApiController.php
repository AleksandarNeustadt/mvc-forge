<?php

namespace App\Controllers;


use App\Core\database\Database;
use App\Core\http\Input;
use App\Core\logging\Logger;
use App\Core\mvc\Controller;
use App\Core\services\ApiResponseFormatterService;
use App\Models\ApiToken;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\Language;
use App\Models\NavigationMenu;
use App\Models\Page;
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
 * API Controller
 * 
 * Handles API requests for content management (pages, menus, posts, categories, tags, languages)
 */
class ApiController extends Controller
{
    private ApiResponseFormatterService $responseFormatter;

    public function __construct(?ApiResponseFormatterService $responseFormatter = null)
    {
        parent::__construct();

        $this->responseFormatter = $responseFormatter ?? new ApiResponseFormatterService();
    }
    /**
     * Login and get API token
     * POST /api/auth/login
     */
    public function login(): void
    {
        Logger::debug('API login request received', [
            'method' => $this->request->method(),
            'uri' => $this->request->uri(),
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? null,
        ]);
        
        $data = Input::json();
        Logger::debug('API login payload source checked', [
            'source' => empty($data) ? 'post' : 'json',
            'fields' => array_keys((array) $data),
        ]);
        
        if (empty($data)) {
            $data = $this->request->post();
        }
        
        $username = $data['username'] ?? $this->request->post('username');
        $password = $data['password'] ?? $this->request->post('password');
        
        Logger::debug('API login credentials presence checked', [
            'username_provided' => !empty($username),
            'password_provided' => !empty($password),
        ]);
        
        if (!$username || !$password) {
            $this->jsonResponse(false, 'Username and password are required', null, 400);
            return;
        }
        
        // Find user by username or email
        $user = User::findByUsername($username);
        if (!$user) {
            $user = User::findByEmail($username);
        }
        
        if (!$user) {
            $this->jsonResponse(false, 'Invalid credentials', null, 401);
            return;
        }
        
        // Check if user is banned
        if ($user->isBanned()) {
            $this->jsonResponse(false, 'Account is banned', null, 403);
            return;
        }
        
        // Check if user is approved
        if (!$user->isApproved()) {
            $this->jsonResponse(false, 'Account is pending approval', null, 403);
            return;
        }
        
        // Verify password
        if (!$user->verifyPassword($password)) {
            $this->jsonResponse(false, 'Invalid credentials', null, 401);
            return;
        }
        
        // Create API token
        $tokenName = $data['token_name'] ?? $this->request->post('token_name', 'API Token');
        $expiresIn = $data['expires_in'] ?? $this->request->post('expires_in'); // seconds, null = never expires
        
        Logger::info('API token issuing started', ['user_id' => $user->id]);
        $apiToken = ApiToken::createToken($user->id, $tokenName, $expiresIn);
        Logger::info('API token issued', [
            'user_id' => $user->id,
            'token_id' => $apiToken->id ?? null,
            'token_preview' => substr(hash('sha256', $apiToken->token), 0, 12),
        ]);
        
        $this->jsonResponse(true, 'Login successful', [
            'token' => $apiToken->token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name
            ],
            'expires_at' => $apiToken->expires_at
        ]);
    }
    
    /**
     * Logout (revoke token)
     * POST /api/auth/logout
     */
    public function logout(): void
    {
        $token = Input::bearerToken();
        
        if (!$token) {
            $this->jsonResponse(false, 'No token provided', null, 400);
            return;
        }
        
        $apiToken = ApiToken::findByToken($token);
        
        if (!$apiToken) {
            $this->jsonResponse(false, 'Invalid token', null, 401);
            return;
        }
        
        $apiToken->revoke();
        
        $this->jsonResponse(true, 'Logged out successfully');
    }
    
    /**
     * Get current user info
     * GET /api/auth/me
     */
    public function me(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(false, 'Not authenticated', null, 401);
            return;
        }
        
        $user = User::find($_SESSION['user_id']);
        
        if (!$user) {
            $this->jsonResponse(false, 'User not found', null, 404);
            return;
        }
        
        $this->jsonResponse(true, 'User info', [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name
        ]);
    }
    
    // ========== PAGES ==========
    
    /**
     * List pages
     * GET /api/pages?language_code=sr
     */
    public function listPages(): void
    {
        $languageCode = $this->request->query('language_code');
        
        $query = Page::query();
        
        if ($languageCode) {
            $language = Language::findByCode($languageCode);
            if ($language) {
                $query->where('language_id', $language->id);
            }
        }
        
        $pages = $query->orderBy('created_at', 'desc')->get();
        
        $result = [];
        foreach ($pages as $page) {
            $pageInstance = new Page();
            $pageData = $pageInstance->newFromBuilder($page);
            $result[] = $this->formatPage($pageData);
        }
        
        $this->jsonResponse(true, 'Pages retrieved', $result);
    }
    
    /**
     * Get single page
     * GET /api/pages/{id}
     */
    public function getPage(int $id): void
    {
        $page = Page::find($id);
        
        if (!$page) {
            $this->jsonResponse(false, 'Page not found', null, 404);
            return;
        }
        
        $this->jsonResponse(true, 'Page retrieved', $this->formatPage($page));
    }
    
    /**
     * Create page
     * POST /api/pages
     */
    public function createPage(): void
    {
        $data = Input::json() ?? $this->request->post();
        
        // Validate required fields
        if (empty($data['title'])) {
            $this->jsonResponse(false, 'Title is required', null, 400);
            return;
        }
        
        // Get language if provided
        $languageId = null;
        if (!empty($data['language_code'])) {
            $language = Language::findByCode($data['language_code']);
            if ($language) {
                $languageId = $language->id;
            }
        }
        
        // Generate slug if not provided
        $slug = $data['slug'] ?? str_slug($data['title']);
        
        // Generate route if not provided
        $route = $data['route'] ?? '/' . $slug;
        
        $pageData = [
            'title' => $data['title'],
            'slug' => $slug,
            'route' => $route,
            'page_type' => $data['page_type'] ?? 'custom',
            'content' => $data['content'] ?? '',
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'is_in_menu' => $data['is_in_menu'] ?? false,
            'menu_order' => $data['menu_order'] ?? 0,
            'parent_page_id' => $data['parent_page_id'] ?? null,
            'navbar_id' => $data['navbar_id'] ?? null,
            'language_id' => $languageId
        ];
        
        $page = Page::create($pageData);
        
        $this->jsonResponse(true, 'Page created', $this->formatPage($page), 201);
    }
    
    /**
     * Update page
     * PUT /api/pages/{id}
     */
    public function updatePage(int $id): void
    {
        $page = Page::find($id);
        
        if (!$page) {
            $this->jsonResponse(false, 'Page not found', null, 404);
            return;
        }
        
        $data = Input::json() ?? $this->request->post();
        
        // Update language if provided
        if (!empty($data['language_code'])) {
            $language = Language::findByCode($data['language_code']);
            if ($language) {
                $data['language_id'] = $language->id;
            }
        }
        
        // Update fields
        foreach ($data as $key => $value) {
            if (in_array($key, $page->getFillable())) {
                $page->$key = $value;
            }
        }
        
        $page->save();
        
        $this->jsonResponse(true, 'Page updated', $this->formatPage($page));
    }
    
    /**
     * Delete page
     * DELETE /api/pages/{id}
     */
    public function deletePage(int $id): void
    {
        $page = Page::find($id);
        
        if (!$page) {
            $this->jsonResponse(false, 'Page not found', null, 404);
            return;
        }
        
        $page->delete();
        
        $this->jsonResponse(true, 'Page deleted');
    }
    
    // ========== MENUS ==========
    
    /**
     * List menus
     * GET /api/menus?language_code=sr
     */
    public function listMenus(): void
    {
        $languageCode = $this->request->query('language_code');
        
        $query = NavigationMenu::query();
        
        if ($languageCode) {
            $language = Language::findByCode($languageCode);
            if ($language) {
                $query->where('language_id', $language->id);
            }
        }
        
        $menus = $query->orderBy('created_at', 'desc')->get();
        
        $result = [];
        foreach ($menus as $menu) {
            $menuInstance = new NavigationMenu();
            $menuData = $menuInstance->newFromBuilder($menu);
            $result[] = $this->formatMenu($menuData);
        }
        
        $this->jsonResponse(true, 'Menus retrieved', $result);
    }
    
    /**
     * Get single menu
     * GET /api/menus/{id}
     */
    public function getMenu(int $id): void
    {
        $menu = NavigationMenu::find($id);
        
        if (!$menu) {
            $this->jsonResponse(false, 'Menu not found', null, 404);
            return;
        }
        
        $this->jsonResponse(true, 'Menu retrieved', $this->formatMenu($menu));
    }
    
    /**
     * Create menu
     * POST /api/menus
     */
    public function createMenu(): void
    {
        $data = Input::json() ?? $this->request->post();
        
        if (empty($data['name'])) {
            $this->jsonResponse(false, 'Name is required', null, 400);
            return;
        }
        
        $languageId = null;
        if (!empty($data['language_code'])) {
            $language = Language::findByCode($data['language_code']);
            if ($language) {
                $languageId = $language->id;
            }
        }
        
        $menuData = [
            'name' => $data['name'],
            'position' => $data['position'] ?? 'header',
            'is_active' => $data['is_active'] ?? true,
            'menu_order' => $data['menu_order'] ?? 0,
            'language_id' => $languageId
        ];
        
        $menu = NavigationMenu::create($menuData);
        
        $this->jsonResponse(true, 'Menu created', $this->formatMenu($menu), 201);
    }
    
    /**
     * Update menu
     * PUT /api/menus/{id}
     */
    public function updateMenu(int $id): void
    {
        $menu = NavigationMenu::find($id);
        
        if (!$menu) {
            $this->jsonResponse(false, 'Menu not found', null, 404);
            return;
        }
        
        $data = Input::json() ?? $this->request->post();
        
        if (!empty($data['language_code'])) {
            $language = Language::findByCode($data['language_code']);
            if ($language) {
                $data['language_id'] = $language->id;
            }
        }
        
        foreach ($data as $key => $value) {
            if (in_array($key, $menu->getFillable())) {
                $menu->$key = $value;
            }
        }
        
        $menu->save();
        
        $this->jsonResponse(true, 'Menu updated', $this->formatMenu($menu));
    }
    
    /**
     * Delete menu
     * DELETE /api/menus/{id}
     */
    public function deleteMenu(int $id): void
    {
        $menu = NavigationMenu::find($id);
        
        if (!$menu) {
            $this->jsonResponse(false, 'Menu not found', null, 404);
            return;
        }
        
        $menu->delete();
        
        $this->jsonResponse(true, 'Menu deleted');
    }
    
    // ========== POSTS ==========
    
    /**
     * List posts
     * GET /api/posts?language_code=sr
     * GET /api/posts?language_id=1
     */
    public function listPosts(): void
    {
        $languageCode = $this->request->query('language_code');
        $languageId = $this->request->query('language_id');
        
        $query = BlogPost::query();
        
        // Podrška za language_id direktno (brže)
        if ($languageId) {
            $query->where('language_id', (int)$languageId);
        } elseif ($languageCode) {
            // Podrška za language_code (traži po kodu)
            $language = Language::findByCode($languageCode);
            if ($language) {
                $query->where('language_id', $language->id);
            }
        }
        
        $posts = $query->orderBy('created_at', 'desc')->get();
        
        $result = [];
        foreach ($posts as $post) {
            $postInstance = new BlogPost();
            $postData = $postInstance->newFromBuilder($post);
            $result[] = $this->formatPost($postData);
        }
        
        $this->jsonResponse(true, 'Posts retrieved', $result);
    }
    
    /**
     * Get single post
     * GET /api/posts/{id}
     */
    public function getPost(int $id): void
    {
        $post = BlogPost::find($id);
        
        if (!$post) {
            $this->jsonResponse(false, 'Post not found', null, 404);
            return;
        }
        
        $this->jsonResponse(true, 'Post retrieved', $this->formatPost($post));
    }
    
    /**
     * Create post
     * POST /api/posts
     */
    public function createPost(): void
    {
        $data = Input::json() ?? $this->request->post();
        
        if (empty($data['title'])) {
            $this->jsonResponse(false, 'Title is required', null, 400);
            return;
        }
        
        $languageId = null;
        if (!empty($data['language_code'])) {
            $language = Language::findByCode($data['language_code']);
            if ($language) {
                $languageId = $language->id;
            }
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        
        $postData = [
            'title' => $data['title'],
            'slug' => $data['slug'] ?? str_slug($data['title']),
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'] ?? '',
            'featured_image' => $data['featured_image'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'published_at' => $data['published_at'] ?? null,
            'author_id' => $userId,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
            'language_id' => $languageId
        ];
        
        $post = BlogPost::create($postData);
        
        // Attach categories if provided
        if (!empty($data['category_ids']) && is_array($data['category_ids'])) {
            $post->syncCategories($data['category_ids']);
        }
        
        // Attach tags if provided
        if (!empty($data['tag_ids']) && is_array($data['tag_ids'])) {
            $post->syncTags($data['tag_ids']);
        }
        
        $this->jsonResponse(true, 'Post created', $this->formatPost($post), 201);
    }
    
    /**
     * Update post
     * PUT /api/posts/{id}
     */
    public function updatePost(int $id): void
    {
        $post = BlogPost::find($id);
        
        if (!$post) {
            $this->jsonResponse(false, 'Post not found', null, 404);
            return;
        }
        
        $data = Input::json() ?? $this->request->post();
        
        if (!empty($data['language_code'])) {
            $language = Language::findByCode($data['language_code']);
            if ($language) {
                $data['language_id'] = $language->id;
            }
        }
        
        foreach ($data as $key => $value) {
            if (in_array($key, $post->getFillable()) && $key !== 'category_ids' && $key !== 'tag_ids') {
                $post->$key = $value;
            }
        }
        
        $post->save();
        
        // Update categories if provided
        if (isset($data['category_ids']) && is_array($data['category_ids'])) {
            $post->syncCategories($data['category_ids']);
        }
        
        // Update tags if provided
        if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
            $post->syncTags($data['tag_ids']);
        }
        
        $this->jsonResponse(true, 'Post updated', $this->formatPost($post));
    }
    
    /**
     * Bulk create posts (for AI translation workflows)
     * POST /api/posts/bulk
     * 
     * Accepts array of posts to create in a single transaction
     * Example: {"posts": [{"title": "...", "content": "...", "language_code": "nl"}, ...]}
     */
    public function bulkCreatePosts(): void
    {
        $data = Input::json() ?? $this->request->post();
        $posts = $data['posts'] ?? [];
        
        if (empty($posts) || !is_array($posts)) {
            $this->jsonResponse(false, 'Posts array is required', null, 400);
            return;
        }
        
        // Limit bulk operations to prevent abuse (max 100 posts per request)
        if (count($posts) > 100) {
            $this->jsonResponse(false, 'Maximum 100 posts allowed per bulk request', null, 400);
            return;
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        $created = [];
        $errors = [];
        
        // Start transaction for atomicity
        Database::beginTransaction();
        
        try {
            foreach ($posts as $index => $postData) {
                try {
                    if (empty($postData['title'])) {
                        $errors[] = [
                            'index' => $index,
                            'error' => 'Title is required'
                        ];
                        continue;
                    }
                    
                    // Resolve language
                    $languageId = null;
                    if (!empty($postData['language_code'])) {
                        $language = Language::findByCode($postData['language_code']);
                        if ($language) {
                            $languageId = $language->id;
                        } else {
                            $errors[] = [
                                'index' => $index,
                                'error' => "Language code '{$postData['language_code']}' not found"
                            ];
                            continue;
                        }
                    }
                    
                    // Create post
                    $newPostData = [
                        'title' => $postData['title'],
                        'slug' => $postData['slug'] ?? str_slug($postData['title']),
                        'excerpt' => $postData['excerpt'] ?? null,
                        'content' => $postData['content'] ?? '',
                        'featured_image' => $postData['featured_image'] ?? null,
                        'status' => $postData['status'] ?? 'draft',
                        'published_at' => $postData['published_at'] ?? null,
                        'author_id' => $userId,
                        'meta_title' => $postData['meta_title'] ?? null,
                        'meta_description' => $postData['meta_description'] ?? null,
                        'meta_keywords' => $postData['meta_keywords'] ?? null,
                        'language_id' => $languageId
                    ];
                    
                    $post = BlogPost::create($newPostData);
                    
                    // Handle categories (create if needed)
                    if (!empty($postData['categories']) && is_array($postData['categories'])) {
                        $categoryIds = [];
                        foreach ($postData['categories'] as $catName) {
                            // Find or create category
                            $category = BlogCategory::query()
                                ->where('name', $catName)
                                ->where('language_id', $languageId)
                                ->first();
                            
                            if (!$category) {
                                $category = BlogCategory::create([
                                    'name' => $catName,
                                    'slug' => str_slug($catName),
                                    'language_id' => $languageId,
                                    'description' => null
                                ]);
                            }
                            
                            $categoryIds[] = $category->id;
                        }
                        $post->syncCategories($categoryIds);
                    }
                    
                    // Handle tags (create if needed)
                    if (!empty($postData['tags']) && is_array($postData['tags'])) {
                        $tagIds = [];
                        foreach ($postData['tags'] as $tagName) {
                            // Find or create tag
                            $tag = BlogTag::query()
                                ->where('name', $tagName)
                                ->where('language_id', $languageId)
                                ->first();
                            
                            if (!$tag) {
                                $tag = BlogTag::create([
                                    'name' => $tagName,
                                    'slug' => str_slug($tagName),
                                    'language_id' => $languageId
                                ]);
                            }
                            
                            $tagIds[] = $tag->id;
                        }
                        $post->syncTags($tagIds);
                    }
                    
                    $created[] = $this->formatPost($post);
                    
                } catch (Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // Commit transaction if at least some posts were created
            if (!empty($created)) {
                Database::commit();
                $this->jsonResponse(true, 'Bulk operation completed', [
                    'created' => $created,
                    'created_count' => count($created),
                    'errors' => $errors,
                    'error_count' => count($errors)
                ], 201);
            } else {
                // Rollback if nothing was created
                Database::rollback();
                $this->jsonResponse(false, 'No posts were created', [
                    'errors' => $errors
                ], 400);
            }
            
        } catch (Exception $e) {
            Database::rollback();
            $this->jsonResponse(false, 'Bulk operation failed: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Delete post
     * DELETE /api/posts/{id}
     */
    public function deletePost(int $id): void
    {
        $post = BlogPost::find($id);
        
        if (!$post) {
            $this->jsonResponse(false, 'Post not found', null, 404);
            return;
        }
        
        $post->delete();
        
        $this->jsonResponse(true, 'Post deleted');
    }
    
    // ========== CATEGORIES ==========
    
    /**
     * List categories
     * GET /api/categories?language_code=sr
     */
    public function listCategories(): void
    {
        $languageCode = $this->request->query('language_code');
        
        $query = BlogCategory::query();
        
        if ($languageCode) {
            $language = Language::findByCode($languageCode);
            if ($language) {
                $query->where('language_id', $language->id);
            }
        }
        
        $categories = $query->orderBy('sort_order', 'asc')->get();
        
        $result = [];
        foreach ($categories as $category) {
            $categoryInstance = new BlogCategory();
            $categoryData = $categoryInstance->newFromBuilder($category);
            $result[] = $this->formatCategory($categoryData);
        }
        
        $this->jsonResponse(true, 'Categories retrieved', $result);
    }
    
    /**
     * Get single category
     * GET /api/categories/{id}
     */
    public function getCategory(int $id): void
    {
        $category = BlogCategory::find($id);
        
        if (!$category) {
            $this->jsonResponse(false, 'Category not found', null, 404);
            return;
        }
        
        $this->jsonResponse(true, 'Category retrieved', $this->formatCategory($category));
    }
    
    /**
     * Create category
     * POST /api/categories
     */
    public function createCategory(): void
    {
        $data = Input::json() ?? $this->request->post();
        
        if (empty($data['name'])) {
            $this->jsonResponse(false, 'Name is required', null, 400);
            return;
        }
        
        $languageId = null;
        if (!empty($data['language_code'])) {
            $language = Language::findByCode($data['language_code']);
            if ($language) {
                $languageId = $language->id;
            }
        }
        
        $categoryData = [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? str_slug($data['name']),
            'description' => $data['description'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'image' => $data['image'] ?? null,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'language_id' => $languageId
        ];
        
        $category = BlogCategory::create($categoryData);
        
        $this->jsonResponse(true, 'Category created', $this->formatCategory($category), 201);
    }
    
    /**
     * Update category
     * PUT /api/categories/{id}
     */
    public function updateCategory(int $id): void
    {
        $category = BlogCategory::find($id);
        
        if (!$category) {
            $this->jsonResponse(false, 'Category not found', null, 404);
            return;
        }
        
        $data = Input::json() ?? $this->request->post();
        
        if (!empty($data['language_code'])) {
            $language = Language::findByCode($data['language_code']);
            if ($language) {
                $data['language_id'] = $language->id;
            }
        }
        
        foreach ($data as $key => $value) {
            if (in_array($key, $category->getFillable())) {
                $category->$key = $value;
            }
        }
        
        $category->save();
        
        $this->jsonResponse(true, 'Category updated', $this->formatCategory($category));
    }
    
    /**
     * Delete category
     * DELETE /api/categories/{id}
     */
    public function deleteCategory(int $id): void
    {
        $category = BlogCategory::find($id);
        
        if (!$category) {
            $this->jsonResponse(false, 'Category not found', null, 404);
            return;
        }
        
        $category->delete();
        
        $this->jsonResponse(true, 'Category deleted');
    }
    
    // ========== TAGS ==========
    
    /**
     * List tags
     * GET /api/tags?language_code=sr
     */
    public function listTags(): void
    {
        $languageCode = $this->request->query('language_code');
        
        $query = BlogTag::query();
        
        if ($languageCode) {
            $language = Language::findByCode($languageCode);
            if ($language) {
                $query->where('language_id', $language->id);
            }
        }
        
        $tags = $query->orderBy('name', 'asc')->get();
        
        $result = [];
        foreach ($tags as $tag) {
            $tagInstance = new BlogTag();
            $tagData = $tagInstance->newFromBuilder($tag);
            $result[] = $this->formatTag($tagData);
        }
        
        $this->jsonResponse(true, 'Tags retrieved', $result);
    }
    
    /**
     * Get single tag
     * GET /api/tags/{id}
     */
    public function getTag(int $id): void
    {
        $tag = BlogTag::find($id);
        
        if (!$tag) {
            $this->jsonResponse(false, 'Tag not found', null, 404);
            return;
        }
        
        $this->jsonResponse(true, 'Tag retrieved', $this->formatTag($tag));
    }
    
    /**
     * Create tag
     * POST /api/tags
     */
    public function createTag(): void
    {
        $data = Input::json() ?? $this->request->post();
        
        if (empty($data['name'])) {
            $this->jsonResponse(false, 'Name is required', null, 400);
            return;
        }
        
        $languageId = null;
        if (!empty($data['language_code'])) {
            $language = Language::findByCode($data['language_code']);
            if ($language) {
                $languageId = $language->id;
            }
        }
        
        $tagData = [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? str_slug($data['name']),
            'description' => $data['description'] ?? null,
            'language_id' => $languageId
        ];
        
        $tag = BlogTag::create($tagData);
        
        $this->jsonResponse(true, 'Tag created', $this->formatTag($tag), 201);
    }
    
    /**
     * Update tag
     * PUT /api/tags/{id}
     */
    public function updateTag(int $id): void
    {
        $tag = BlogTag::find($id);
        
        if (!$tag) {
            $this->jsonResponse(false, 'Tag not found', null, 404);
            return;
        }
        
        $data = Input::json() ?? $this->request->post();
        
        if (!empty($data['language_code'])) {
            $language = Language::findByCode($data['language_code']);
            if ($language) {
                $data['language_id'] = $language->id;
            }
        }
        
        foreach ($data as $key => $value) {
            if (in_array($key, $tag->getFillable())) {
                $tag->$key = $value;
            }
        }
        
        $tag->save();
        
        $this->jsonResponse(true, 'Tag updated', $this->formatTag($tag));
    }
    
    /**
     * Delete tag
     * DELETE /api/tags/{id}
     */
    public function deleteTag(int $id): void
    {
        $tag = BlogTag::find($id);
        
        if (!$tag) {
            $this->jsonResponse(false, 'Tag not found', null, 404);
            return;
        }
        
        $tag->delete();
        
        $this->jsonResponse(true, 'Tag deleted');
    }
    
    // ========== LANGUAGES ==========
    
    /**
     * List languages
     * GET /api/languages
     */
    public function listLanguages(): void
    {
        $languages = Language::getActive();
        
        $result = [];
        foreach ($languages as $language) {
            $result[] = $this->formatLanguage($language);
        }
        
        $this->jsonResponse(true, 'Languages retrieved', $result);
    }
    
    /**
     * Get single language
     * GET /api/languages/{id}
     */
    public function getLanguage(int $id): void
    {
        $language = Language::find($id);
        
        if (!$language) {
            $this->jsonResponse(false, 'Language not found', null, 404);
            return;
        }
        
        $this->jsonResponse(true, 'Language retrieved', $this->formatLanguage($language));
    }
    
    /**
     * Get language by code
     * GET /api/languages/code/{code}
     */
    public function getLanguageByCode(string $code): void
    {
        $language = Language::findByCode($code);
        
        if (!$language) {
            $this->jsonResponse(false, 'Language not found', null, 404);
            return;
        }
        
        $this->jsonResponse(true, 'Language retrieved', $this->formatLanguage($language));
    }
    
    /**
     * Create language
     * POST /api/languages
     */
    public function createLanguage(): void
    {
        $data = Input::json() ?? $this->request->post();
        
        if (empty($data['code']) || empty($data['name'])) {
            $this->jsonResponse(false, 'Code and name are required', null, 400);
            return;
        }
        
        $languageData = [
            'code' => $data['code'],
            'name' => $data['name'],
            'native_name' => $data['native_name'] ?? $data['name'],
            'flag' => $data['flag'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'is_default' => $data['is_default'] ?? false,
            'sort_order' => $data['sort_order'] ?? 0
        ];
        
        $language = Language::create($languageData);
        
        // If this is set as default, unset others
        if ($languageData['is_default']) {
            $language->setAsDefault();
        }
        
        $this->jsonResponse(true, 'Language created', $this->formatLanguage($language), 201);
    }
    
    /**
     * Update language
     * PUT /api/languages/{id}
     */
    public function updateLanguage(int $id): void
    {
        $language = Language::find($id);
        
        if (!$language) {
            $this->jsonResponse(false, 'Language not found', null, 404);
            return;
        }
        
        $data = Input::json() ?? $this->request->post();
        
        foreach ($data as $key => $value) {
            if (in_array($key, $language->getFillable())) {
                $language->$key = $value;
            }
        }
        
        $language->save();
        
        // If set as default, unset others
        if (isset($data['is_default']) && $data['is_default']) {
            $language->setAsDefault();
        }
        
        $this->jsonResponse(true, 'Language updated', $this->formatLanguage($language));
    }
    
    /**
     * Delete language
     * DELETE /api/languages/{id}
     */
    public function deleteLanguage(int $id): void
    {
        $language = Language::find($id);
        
        if (!$language) {
            $this->jsonResponse(false, 'Language not found', null, 404);
            return;
        }
        
        // Check if language is default
        if ($language->is_default) {
            $this->jsonResponse(false, 'Cannot delete default language', null, 400);
            return;
        }
        
        // Check if language has content
        $pageCount = Database::select("SELECT COUNT(*) as count FROM pages WHERE language_id = ?", [$id])[0]['count'] ?? 0;
        $menuCount = Database::select("SELECT COUNT(*) as count FROM navigation_menus WHERE language_id = ?", [$id])[0]['count'] ?? 0;
        $postCount = Database::select("SELECT COUNT(*) as count FROM blog_posts WHERE language_id = ?", [$id])[0]['count'] ?? 0;
        $categoryCount = Database::select("SELECT COUNT(*) as count FROM blog_categories WHERE language_id = ?", [$id])[0]['count'] ?? 0;
        $tagCount = Database::select("SELECT COUNT(*) as count FROM blog_tags WHERE language_id = ?", [$id])[0]['count'] ?? 0;
        
        if ($pageCount > 0 || $menuCount > 0 || $postCount > 0 || $categoryCount > 0 || $tagCount > 0) {
            $this->jsonResponse(false, 'Cannot delete language that has associated content. Please remove or reassign content first.', null, 400);
            return;
        }
        
        $language->delete();
        
        $this->jsonResponse(true, 'Language deleted');
    }
    
    // ========== FORMATTING HELPERS ==========
    
    private function formatPage(Page $page): array
    {
        return $this->responseFormatter->formatPage($page);
    }
    
    private function formatMenu(NavigationMenu $menu): array
    {
        return $this->responseFormatter->formatMenu($menu);
    }
    
    private function formatPost(BlogPost $post): array
    {
        return $this->responseFormatter->formatPost($post);
    }
    
    private function formatCategory(BlogCategory $category): array
    {
        return $this->responseFormatter->formatCategory($category);
    }
    
    private function formatTag(BlogTag $tag): array
    {
        return $this->responseFormatter->formatTag($tag);
    }
    
    private function formatLanguage(Language $language): array
    {
        return $this->responseFormatter->formatLanguage($language);
    }
    
    /**
     * Send JSON response
     */
    private function jsonResponse(bool $success, string $message, mixed $data = null, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}


if (!\class_exists('ApiController', false) && !\interface_exists('ApiController', false) && !\trait_exists('ApiController', false)) {
    \class_alias(__NAMESPACE__ . '\\ApiController', 'ApiController');
}
