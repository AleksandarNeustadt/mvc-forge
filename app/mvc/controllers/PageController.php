<?php

namespace App\Controllers;


use App\Core\logging\Logger;
use App\Core\mvc\Controller;
use App\Core\routing\Router;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\Page;use BadMethodCallException;
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
 * Page Controller
 * 
 * Handles dynamic page routes from Page Manager
 */
class PageController extends Controller
{
    private ?Router $router;

    public function __construct(?Router $router = null)
    {
        parent::__construct();
        $this->router = $router;
    }

    private function currentRouter(): ?Router
    {
        if ($this->router instanceof Router) {
            return $this->router;
        }

        if (function_exists('app_router')) {
            return app_router();
        }

        return isset($GLOBALS['router']) && $GLOBALS['router'] instanceof Router
            ? $GLOBALS['router']
            : null;
    }

    private function currentLanguage(): string
    {
        return $this->currentRouter()?->lang ?? 'sr';
    }

    private function currentRouteUri(): string
    {
        return $this->currentRouter()?->getUri() ?? '/';
    }

    /**
     * Get URL for a blog post by finding the page that links to it
     * Uses SEO-friendly format: /category-slug/post-slug
     */
    private function getBlogPostUrl(int $postId): ?string
    {
        $lang = $this->currentLanguage();
        
        $blogPost = BlogPost::find($postId);
        if (!$blogPost) {
            return null;
        }
        
        // Get the first category (primary category) for SEO-friendly URL
        $categories = $blogPost->categories();
        $primaryCategory = null;
        if (!empty($categories) && is_array($categories)) {
            // Get first category (can be enhanced to use a "primary" flag)
            $primaryCategory = $categories[0];
        }
        
        // Build URL: /category-slug/post-slug
        if ($primaryCategory && isset($primaryCategory['slug'])) {
            $categorySlug = $primaryCategory['slug'];
            $postSlug = $blogPost->slug;
            return "/{$lang}/{$categorySlug}/{$postSlug}";
        }
        
        // Fallback: if no category, use direct post slug (backward compatibility)
        // Find page that has this blog_post_id
        $allPages = Page::all();
        foreach ($allPages as $p) {
            if ($p->blog_post_id == $postId && $p->is_active) {
                $route = '/' . ltrim($p->route ?? '', '/');
                return "/{$lang}{$route}";
            }
        }
        
        // Last resort: use post slug directly
        return "/{$lang}/{$blogPost->slug}";
    }

    /**
     * Show a dynamic page
     * 
     * This method is called by the Router when a dynamic route matches
     * The page is loaded from the database based on the route
     * 
     * Route: GET /{route} (dynamic, registered from pages table)
     */
    public function show(): void
    {
        // Get current URI (without language prefix)
        $route = $this->currentRouteUri();
        $lang = $this->currentLanguage();
        
        // Normalize route (ensure it starts with / and doesn't end with /)
        $route = '/' . ltrim($route, '/');
        if ($route !== '/' && substr($route, -1) === '/') {
            $route = rtrim($route, '/');
        }

        Logger::debug("PageController::show() - Looking for route: {$route}");

        // Find page by route using Page model's static method
        // Now with language filtering
        $page = Page::findByRoute($route, $lang);
        
        if ($page) {
            Logger::debug("PageController::show() - Found page: ID={$page->id}, title={$page->title}, application={$page->application}");
        }

        if (!$page) {
            // Try hierarchical URL pattern: /category-slug/post-slug
            // This allows URLs like /projects/mvc-framework
            // BUT: Only try this if there's no exact page match and route doesn't look like a contact form route
            $routeParts = explode('/', trim($route, '/'));
            
            // Skip hierarchical pattern matching for known non-blog routes
            // Check if this might be a contact form route by looking for exact page match one more time
            // This handles edge cases where route normalization might have failed
            $exactPage = Page::findByRoute($route);
            if ($exactPage && $exactPage->is_active) {
                $page = $exactPage;
                Logger::debug("PageController::show() - Found page via findByRoute: ID={$page->id}, title={$page->title}, application={$page->application}");
            } else {
                // Only try hierarchical patterns if it's not a single-part route that might be a custom page
                // For single-part routes, we should only try blog category if it's clearly a blog route
                if (count($routeParts) === 2) {
                    $categorySlug = $routeParts[0];
                    $postSlug = $routeParts[1];
                    
                    Logger::debug("PageController::show() - Trying hierarchical URL - Category: {$categorySlug}, Post: {$postSlug}");
                    
                    // Find category by slug
                    $category = BlogCategory::findBySlug($categorySlug);
                    if ($category) {
                        Logger::debug("PageController::show() - Found category: ID={$category->id}, Name={$category->name}");
                        // Find post by slug within this category
                        $blogPost = BlogPost::findBySlug($postSlug);
                        if ($blogPost) {
                            Logger::debug("PageController::show() - Found post: ID={$blogPost->id}, Title={$blogPost->title}");
                            // Verify post belongs to this category
                            $postCategories = $blogPost->categories();
                            Logger::debug("PageController::show() - Post categories: " . json_encode($postCategories));
                            Logger::debug("PageController::show() - Looking for category ID: " . $category->id);
                            
                            // Handle both array of arrays and array of objects
                            $postCategoryIds = [];
                            foreach ($postCategories as $cat) {
                                if (is_array($cat)) {
                                    $postCategoryIds[] = $cat['id'] ?? null;
                                } elseif (is_object($cat)) {
                                    $postCategoryIds[] = $cat->id ?? null;
                                }
                            }
                            $postCategoryIds = array_filter($postCategoryIds);
                            
                            Logger::debug("PageController::show() - Post category IDs: " . json_encode($postCategoryIds));
                            
                            if (in_array($category->id, $postCategoryIds)) {
                                // Found matching post in category - render it
                                Logger::debug("PageController::show() - Post belongs to category, rendering post");
                                $postArray = $blogPost->toArray();
                                $categories = $blogPost->categories();
                                $postArray['categories'] = is_array($categories) ? $categories : [];
                                
                                $author = $blogPost->author();
                                $postArray['author'] = $author ? $author->toArray() : null;
                                $postArray['url'] = $this->getBlogPostUrl($blogPost->id);
                                
                                $data = [
                                    'page' => [
                                        'title' => $blogPost->title,
                                        'meta_title' => $blogPost->meta_title ?? $blogPost->title,
                                        'meta_description' => $blogPost->meta_description ?? $blogPost->excerpt,
                                        'meta_keywords' => $blogPost->meta_keywords ?? '',
                                    ],
                                    'pageType' => 'blog_post',
                                    'application' => 'blog',
                                    'blogPost' => $postArray,
                                ];
                                
                                $this->view('blog/single', $data);
                                return;
                            } else {
                                Logger::debug("PageController::show() - Post does NOT belong to category. Post category IDs: " . json_encode($postCategoryIds) . ", Looking for: {$category->id}");
                            }
                        } else {
                            Logger::debug("PageController::show() - Post not found with slug: {$postSlug}");
                        }
                    } else {
                        Logger::debug("PageController::show() - Category not found with slug: {$categorySlug}");
                    }
                } elseif (count($routeParts) === 1) {
                    // Try category listing: /category-slug
                    // BUT: First check if there's a page with this exact route to avoid false matches
                    $categorySlug = $routeParts[0];
                    
                    // Re-check for exact page match one more time (in case normalization issue)
                    $allPagesAgain = Page::all();
                    foreach ($allPagesAgain as $p) {
                        $pageRoute = '/' . ltrim($p->route ?? '', '/');
                        if ($pageRoute !== '/' && substr($pageRoute, -1) === '/') {
                            $pageRoute = rtrim($pageRoute, '/');
                        }
                        
                        if ($pageRoute === $route && $p->is_active) {
                            $page = $p;
                            Logger::debug("PageController::show() - Found page on second pass: ID={$page->id}, title={$page->title}, application={$page->application}");
                            break;
                        }
                    }
                    
                    // Only try category matching if no page was found
                    if (!$page) {
                        $category = BlogCategory::findBySlug($categorySlug, $lang);
                        
                        if ($category) {
                            // Check if there's a page for this category for the current language
                            $categoryPage = Page::query()
                                ->where('blog_category_id', $category->id)
                                ->where('language_id', $category->language_id)
                                ->where('is_active', 1)
                                ->first();
                            
                            // If found, convert to model instance
                            if ($categoryPage) {
                                $instance = new Page();
                                $page = $instance->newFromBuilder($categoryPage);
                            } else {
                                // Render category listing dynamically
                                $data['category'] = $category->toArray();
                                $posts = $category->posts();
                                $postsArray = is_array($posts) ? $posts : [];
                                
                                foreach ($postsArray as &$postArray) {
                                    $postId = $postArray['id'] ?? null;
                                    $postArray['url'] = $postId ? $this->getBlogPostUrl($postId) : null;
                                }
                                unset($postArray);
                                
                                $data = [
                                    'page' => [
                                        'title' => $category->name,
                                        'meta_title' => $category->meta_title ?? $category->name,
                                        'meta_description' => $category->meta_description ?? $category->description,
                                    ],
                                    'pageType' => 'blog_category',
                                    'application' => 'blog',
                                    'category' => $category->toArray(),
                                    'posts' => $postsArray,
                                    'displayOptions' => [
                                        'style' => 'list',
                                        'posts_per_page' => 10,
                                        'show_excerpt' => true,
                                        'show_featured_image' => true,
                                        'grid_columns' => 3
                                    ],
                                ];
                                
                                $this->view('blog/category', $data);
                                return;
                            }
                        }
                    }
                }
            }
            
            // If still no page found after trying hierarchical patterns
            if (!$page) {
                Logger::debug("PageController::show() - Page not found for route: {$route}");
                $this->abort(404, 'Page not found');
                return;
            }
        }

        // Determine which view to render based on page type and application
        // Map new page_type values (from form) to old values (in database) for backward compatibility
        $pageType = $page->page_type ?? 'custom';
        $application = $page->application ?? null;
        
        // If application is 'homepage', render homepage with components
        if ($application === 'homepage') {
            // Handle POST requests for contact form (if enabled)
            if ($this->request->isPost()) {
                // Check if contact form is enabled in homepage options
                $homepageOptions = [];
                if (isset($page->display_options)) {
                    if (is_string($page->display_options)) {
                        $homepageOptions = json_decode($page->display_options, true) ?? [];
                    } elseif (is_array($page->display_options)) {
                        $homepageOptions = $page->display_options;
                    }
                }
                
                $enableContactForm = $homepageOptions['enable_contact_form'] ?? false;
                
                if ($enableContactForm) {
                    // Delegate POST requests to MainController::contact()
                    // MainController will handle all security checks and form processing
                    $mainController = new MainController();
                    $mainController->contact();
                    return;
                }
            }
            
            // For GET requests, render homepage view
            $data = [
                'page' => $page->toArray(),
                'pageType' => $pageType,
                'application' => $application,
            ];
            $this->view('homepage', $data);
            return;
        }
        
        // If application is 'contact', render contact form immediately (both GET and POST)
        // This takes priority over page_type to ensure contact forms work correctly
        if ($application === 'contact') {
            if ($this->request->isPost()) {
                // Delegate POST requests to MainController::contact()
                // MainController will handle all security checks and form processing
                $mainController = new MainController();
                $mainController->contact();
                return;
            } else {
                // For GET requests, render contact form
                $data = [
                    'page' => $page->toArray(),
                    'pageType' => $pageType,
                    'application' => $application,
                ];
                $this->view('contact', $data);
                return;
            }
        }
        
        // If application is 'blog', map new page_type values to old ones
        if ($application === 'blog') {
            $pageTypeMap = [
                'single_post' => 'blog_post',
                'category' => 'blog_category',
                'tag' => 'blog_tag',
                'list' => 'blog_list'
            ];
            if (isset($pageTypeMap[$pageType])) {
                $pageType = $pageTypeMap[$pageType];
            }
        }

        // Prepare data for the view
        $data = [
            'page' => $page->toArray(),
            'pageType' => $pageType,
            'application' => $application,
        ];

        $appLog = $application !== null ? $application : 'null';
        Logger::debug("PageController::show() - Preparing to render view. Page type: {$pageType}, Application: {$appLog}");

        // Render based on page type
        switch ($pageType) {
            case 'blog_post':
                // Single blog post
                // Only render if this is actually a blog application and has a valid blog post
                if ($application === 'blog' && $page->blog_post_id) {
                    $blogPost = BlogPost::find($page->blog_post_id);
                    if ($blogPost) {
                        $postArray = $blogPost->toArray();
                        // categories() and tags() return arrays (from QueryBuilder::get()), not model instances
                        $categories = $blogPost->categories();
                        $tags = $blogPost->tags();
                        $postArray['categories'] = is_array($categories) ? $categories : [];
                        $postArray['tags'] = is_array($tags) ? $tags : [];
                        
                        // Get author (returns Model instance, so use toArray())
                        $author = $blogPost->author();
                        $postArray['author'] = $author ? $author->toArray() : null;
                        
                        // Add URL
                        $postArray['url'] = $this->getBlogPostUrl($blogPost->id);
                        
                        $data['blogPost'] = $postArray;
                        $this->view('blog/single', $data);
                        return;
                    } else {
                        // Blog post not found - show 404
                        Logger::debug("PageController::show() - Blog post not found for page ID: {$page->id}, blog_post_id: {$page->blog_post_id}");
                        $this->abort(404, 'Blog post not found');
                        return;
                    }
                } else {
                    // Page type is blog_post but not a blog application or missing blog_post_id
                    // Fall through to custom/default handling
                    Logger::debug("PageController::show() - Page has page_type='blog_post' but application='{$application}' or missing blog_post_id. Treating as custom page.");
                    // Continue to default case
                }
                // Fall through to default case if conditions not met

            case 'blog_category':
                // Blog category listing
                if ($page->blog_category_id) {
                    $category = BlogCategory::find($page->blog_category_id);
                    if ($category) {
                        $data['category'] = $category->toArray();
                        // posts() returns arrays (from QueryBuilder::get()), not model instances
                        $posts = $category->posts();
                        $postsArray = is_array($posts) ? $posts : [];
                        
                        // Add URLs to posts
                        foreach ($postsArray as &$postArray) {
                            $postId = $postArray['id'] ?? null;
                            $postArray['url'] = $postId ? $this->getBlogPostUrl($postId) : null;
                        }
                        unset($postArray);
                        
                        // Get display options and apply posts_per_page limit
                        $displayOptionsRaw = $page->display_options;
                        // Ensure display_options is an array
                        if (is_string($displayOptionsRaw)) {
                            $displayOptionsRaw = json_decode($displayOptionsRaw, true) ?? [];
                        }
                        $displayOptions = is_array($displayOptionsRaw) ? $displayOptionsRaw : [];
                        // Ensure we have default values
                        $displayOptions = array_merge([
                            'style' => 'list',
                            'posts_per_page' => 10,
                            'show_excerpt' => true,
                            'show_featured_image' => true,
                            'grid_columns' => 3
                        ], $displayOptions);
                        
                        Logger::debug('DEBUG PageController category - displayOptions: ' . json_encode($displayOptions));
                        $styleLog = isset($displayOptions['style']) ? $displayOptions['style'] : 'not set';
                        Logger::debug('DEBUG PageController category - style: ' . $styleLog);
                        
                        $postsPerPage = (int) ($displayOptions['posts_per_page'] ?? 10);
                        if ($postsPerPage > 0 && count($postsArray) > $postsPerPage) {
                            $postsArray = array_slice($postsArray, 0, $postsPerPage);
                        }
                        
                        $data['posts'] = $postsArray;
                        $data['displayOptions'] = $displayOptions;
                    }
                }
                $this->view('blog/category', $data);
                break;

            case 'blog_tag':
                // Blog tag listing
                if ($page->blog_tag_id) {
                    $tag = BlogTag::find($page->blog_tag_id);
                    if ($tag) {
                        $data['tag'] = $tag->toArray();
                        // posts() returns arrays (from QueryBuilder::get()), not model instances
                        $posts = $tag->posts();
                        $postsArray = is_array($posts) ? $posts : [];
                        
                        // Add URLs to posts
                        foreach ($postsArray as &$postArray) {
                            $postId = $postArray['id'] ?? null;
                            $postArray['url'] = $postId ? $this->getBlogPostUrl($postId) : null;
                        }
                        unset($postArray);
                        
                        // Get display options and apply posts_per_page limit
                        $displayOptionsRaw = $page->display_options;
                        // Ensure display_options is an array
                        if (is_string($displayOptionsRaw)) {
                            $displayOptionsRaw = json_decode($displayOptionsRaw, true) ?? [];
                        }
                        $displayOptions = is_array($displayOptionsRaw) ? $displayOptionsRaw : [];
                        // Ensure we have default values
                        $displayOptions = array_merge([
                            'style' => 'list',
                            'posts_per_page' => 10,
                            'show_excerpt' => true,
                            'show_featured_image' => true,
                            'grid_columns' => 3
                        ], $displayOptions);
                        
                        $postsPerPage = (int) ($displayOptions['posts_per_page'] ?? 10);
                        if ($postsPerPage > 0 && count($postsArray) > $postsPerPage) {
                            $postsArray = array_slice($postsArray, 0, $postsPerPage);
                        }
                        
                        $data['posts'] = $postsArray;
                        $data['displayOptions'] = $displayOptions;
                    }
                }
                $this->view('blog/tag', $data);
                break;

            case 'blog_list':
                // Blog post list
                $posts = BlogPost::query()
                    ->where('status', 'published')
                    ->orderBy('published_at', 'DESC')
                    ->get();
                
                // QueryBuilder::get() returns arrays, not model instances
                $postsArray = is_array($posts) ? $posts : [];
                
                // Enrich each post with categories, tags, and URLs
                foreach ($postsArray as &$postArray) {
                    // Find the post model to get relations
                    $postId = $postArray['id'] ?? null;
                    if ($postId) {
                        $postModel = BlogPost::find($postId);
                        if ($postModel) {
                            $categories = $postModel->categories();
                            $tags = $postModel->tags();
                            $postArray['categories'] = is_array($categories) ? $categories : [];
                            $postArray['tags'] = is_array($tags) ? $tags : [];
                            
                            // Add URL
                            $postArray['url'] = $this->getBlogPostUrl($postId);
                        } else {
                            $postArray['categories'] = [];
                            $postArray['tags'] = [];
                            $postArray['url'] = null;
                        }
                    } else {
                        $postArray['categories'] = [];
                        $postArray['tags'] = [];
                        $postArray['url'] = null;
                    }
                }
                unset($postArray); // Break reference
                
                // Get display options and apply posts_per_page limit
                $displayOptionsRaw = $page->display_options;
                // Ensure display_options is an array
                if (is_string($displayOptionsRaw)) {
                    $displayOptionsRaw = json_decode($displayOptionsRaw, true) ?? [];
                }
                $displayOptions = is_array($displayOptionsRaw) ? $displayOptionsRaw : [];
                // Ensure we have default values
                $displayOptions = array_merge([
                    'style' => 'list',
                    'posts_per_page' => 10,
                    'show_excerpt' => true,
                    'show_featured_image' => true,
                    'grid_columns' => 3
                ], $displayOptions);
                
                $postsPerPage = (int) ($displayOptions['posts_per_page'] ?? 10);
                if ($postsPerPage > 0 && count($postsArray) > $postsPerPage) {
                    $postsArray = array_slice($postsArray, 0, $postsPerPage);
                }
                
                $data['posts'] = $postsArray;
                $data['displayOptions'] = $displayOptions;
                
                $this->view('blog/list', $data);
                break;

            case 'custom':
            default:
                // Check if application is 'contact' - render contact form
                if ($application === 'contact') {
                    $this->view('contact', $data);
                    break;
                }
                // Custom page - render with template if specified, otherwise default
                $template = $page->template ?? 'default';
                $viewPath = "custom/{$template}";
                
                // Check if custom template exists, otherwise use default
                // Controller::view() adds 'pages/' prefix, so we check the full path
                $fullViewPath = __DIR__ . '/../views/pages/' . $viewPath . '.php';
                Logger::debug("PageController::show() - Checking view path: {$fullViewPath}");
                if (!file_exists($fullViewPath)) {
                    Logger::debug("PageController::show() - View not found, using default");
                    $viewPath = 'custom/default';
                } else {
                    Logger::debug("PageController::show() - View found, rendering: {$viewPath}");
                }
                
                Logger::debug("PageController::show() - Calling view() with: {$viewPath}");
                $this->view($viewPath, $data);
                Logger::debug("PageController::show() - View rendered successfully");
                break;
        }
    }
}


if (!\class_exists('PageController', false) && !\interface_exists('PageController', false) && !\trait_exists('PageController', false)) {
    \class_alias(__NAMESPACE__ . '\\PageController', 'PageController');
}
