<?php
// routes/web.php
// Define application routes using fluent API

use App\Controllers\AuthController;
use App\Controllers\DashboardBlogController;
use App\Controllers\DashboardContactMessageController;
use App\Controllers\DashboardGeoController;
use App\Controllers\DashboardHomeController;
use App\Controllers\DashboardLanguageController;
use App\Controllers\DashboardNavigationController;
use App\Controllers\DashboardPageController;
use App\Controllers\DashboardRoleController;
use App\Controllers\DashboardSchemaController;
use App\Controllers\DashboardUserController;
use App\Controllers\MainController;
use App\Controllers\ResourceController;
use App\Controllers\UserController;

// Homepage - handled by Page Manager (homepage application) or MainController as fallback
// If a homepage page exists in Page Manager with route '/', it will be rendered via PageController
// Otherwise, MainController::home() will show under construction page
Route::get('/', [MainController::class, 'home'])->name('home');

// ========== Authentication Routes ==========
// Login
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware([
        new CsrfMiddleware(),
        new RateLimitMiddleware(5, 60), // 5 attempts per 60 seconds
    ])
    ->name('login.submit');

// Register
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])
    ->middleware([
        new CsrfMiddleware(),
        new RateLimitMiddleware(3, 300), // 3 attempts per 5 minutes
    ])
    ->name('register.submit');

// Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Forgot Password
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.forgot');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware([new RateLimitMiddleware(3, 600)]) // 3 attempts per 10 minutes
    ->name('password.forgot.submit');

// Email Verification
Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail'])->name('email.verify');

// ========== User Routes ==========
// User profile by slug (SEO-friendly)
Route::get('/user/{slug}', [UserController::class, 'show'])
    ->where(['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])
    ->name('user.show');

// Old ID-based route (for backwards compatibility - optional)
Route::get('/user/id/{id}', [UserController::class, 'showById'])
    ->where(['id' => '[0-9]+'])
    ->name('user.show.id');

// ========== Protected Routes ==========
Route::middleware(['auth'])->group(function() {
    Route::get('/profile', [UserController::class, 'profile'])->name('user.profile');
    Route::get('/profile/edit', [UserController::class, 'editProfile'])->name('user.profile.edit');
    Route::post('/profile/update', [UserController::class, 'updateProfile'])->name('user.profile.update');
    Route::put('/profile/update', [UserController::class, 'updateProfile'])->name('user.profile.update');
    
    // Dashboard (Home) - requires dashboard access permission
    Route::get('/dashboard', [DashboardHomeController::class, 'index'])
        ->middleware([new PermissionMiddleware('system.dashboard')])
        ->name('dashboard');
    
    // Database Management - requires database permission
    Route::get('/dashboard/database', [DashboardSchemaController::class, 'database'])
        ->middleware([new PermissionMiddleware('system.database')])
        ->name('dashboard.database');
    
    // IP Tracking - requires dashboard permission
    Route::get('/dashboard/ip-tracking', [DashboardHomeController::class, 'ipTracking'])
        ->middleware([new PermissionMiddleware('system.dashboard')])
        ->name('dashboard.ip-tracking');
    Route::get('/dashboard/database/tables/create', [DashboardSchemaController::class, 'createTable'])->name('dashboard.table.create');
    Route::post('/dashboard/database/tables', [DashboardSchemaController::class, 'storeTable'])->name('dashboard.table.store');
    Route::get('/dashboard/database/tables/{table}', [DashboardSchemaController::class, 'showTable'])->name('dashboard.table');
    Route::delete('/dashboard/database/tables/{table}', [DashboardSchemaController::class, 'dropTable'])->name('dashboard.table.drop');
    Route::get('/dashboard/database/tables/{table}/columns/create', [DashboardSchemaController::class, 'createColumn'])->name('dashboard.column.create');
    Route::post('/dashboard/database/tables/{table}/columns', [DashboardSchemaController::class, 'storeColumn'])->name('dashboard.column.store');
    Route::delete('/dashboard/database/tables/{table}/columns/{column}', [DashboardSchemaController::class, 'dropColumn'])->name('dashboard.column.drop');
    
    // User Management (following blog CRUD pattern)
    // IMPORTANT: More specific routes (with more segments) must come BEFORE less specific ones
    Route::get('/dashboard/users/create', [DashboardUserController::class, 'createUser'])
        ->middleware([new PermissionMiddleware('users.create')])
        ->name('dashboard.user.create');
    Route::get('/dashboard/users/{id}/edit', [DashboardUserController::class, 'editUser'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('users.edit')])
        ->name('dashboard.user.edit');
    Route::post('/dashboard/users/{id}/delete', [DashboardUserController::class, 'deleteUser'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('users.delete')])
        ->name('dashboard.user.delete');
    Route::post('/dashboard/users/{id}/ban', [DashboardUserController::class, 'banUser'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('users.edit')])
        ->name('dashboard.user.ban');
    Route::post('/dashboard/users/{id}/unban', [DashboardUserController::class, 'unbanUser'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('users.edit')])
        ->name('dashboard.user.unban');
    Route::post('/dashboard/users/{id}/approve', [DashboardUserController::class, 'approveUser'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('users.edit')])
        ->name('dashboard.user.approve');
    Route::get('/dashboard/users/{id}', [DashboardUserController::class, 'showUser'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('users.view')])
        ->name('dashboard.user.show');
    Route::post('/dashboard/users/{id}', [DashboardUserController::class, 'updateUser'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('users.edit')])
        ->name('dashboard.user.update');
    Route::put('/dashboard/users/{id}', [DashboardUserController::class, 'updateUser'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('users.edit')])
        ->name('dashboard.user.update.put');
    Route::get('/dashboard/users', [DashboardUserController::class, 'users'])
        ->middleware([new PermissionMiddleware('users.view')])
        ->name('dashboard.users');
    Route::post('/dashboard/users', [DashboardUserController::class, 'storeUser'])
        ->middleware([new PermissionMiddleware('users.create')])
        ->name('dashboard.user.store');
    
    // Roles Management - requires manage roles permission
    Route::get('/dashboard/users/roles', [DashboardRoleController::class, 'roles'])
        ->middleware([new PermissionMiddleware('users.manage-roles')])
        ->name('dashboard.users.roles');
    Route::get('/dashboard/users/roles/create', [DashboardRoleController::class, 'createRole'])
        ->middleware([new PermissionMiddleware('users.manage-roles')])
        ->name('dashboard.users.role.create');
    Route::post('/dashboard/users/roles', [DashboardRoleController::class, 'storeRole'])
        ->middleware([new PermissionMiddleware('users.manage-roles')])
        ->name('dashboard.users.role.store');
    Route::get('/dashboard/users/roles/{id}/edit', [DashboardRoleController::class, 'editRole'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('users.manage-roles')])
        ->name('dashboard.users.role.edit');
    Route::post('/dashboard/users/roles/{id}', [DashboardRoleController::class, 'updateRole'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('users.manage-roles')])
        ->name('dashboard.users.role.update');
    Route::put('/dashboard/users/roles/{id}', [DashboardRoleController::class, 'updateRole'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('users.manage-roles')])
        ->name('dashboard.users.role.update.put');
    Route::post('/dashboard/users/roles/{id}/delete', [DashboardRoleController::class, 'deleteRole'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('users.manage-roles')])
        ->name('dashboard.users.role.delete');
    
    // Permissions Management - requires manage permissions permission
    Route::get('/dashboard/users/permissions', [DashboardRoleController::class, 'permissions'])
        ->middleware([new PermissionMiddleware('users.manage-permissions')])
        ->name('dashboard.users.permissions');
    
    // Additional user actions
    Route::post('/dashboard/users/{id}/ban', [DashboardUserController::class, 'banUser'])->where(['id' => '[0-9]+'])->name('dashboard.user.ban');
    Route::post('/dashboard/users/{id}/unban', [DashboardUserController::class, 'unbanUser'])->where(['id' => '[0-9]+'])->name('dashboard.user.unban');
    Route::post('/dashboard/users/{id}/approve', [DashboardUserController::class, 'approveUser'])->where(['id' => '[0-9]+'])->name('dashboard.user.approve');
    
    // Navigation Menu Management
    Route::get('/dashboard/navigation-menus', [DashboardNavigationController::class, 'navigationMenus'])
        ->middleware([new PermissionMiddleware('pages.view')])
        ->name('dashboard.navigation-menus');
    Route::get('/dashboard/navigation-menus/create', [DashboardNavigationController::class, 'createNavigationMenu'])
        ->middleware([new PermissionMiddleware('pages.view')])
        ->name('dashboard.navigation-menu.create');
    Route::post('/dashboard/navigation-menus', [DashboardNavigationController::class, 'storeNavigationMenu'])
        ->middleware([new PermissionMiddleware('pages.view')])
        ->name('dashboard.navigation-menu.store');
    Route::get('/dashboard/navigation-menus/{id}/edit', [DashboardNavigationController::class, 'editNavigationMenu'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('pages.view')])
        ->name('dashboard.navigation-menu.edit');
    Route::post('/dashboard/navigation-menus/{id}', [DashboardNavigationController::class, 'updateNavigationMenu'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('pages.view')])
        ->name('dashboard.navigation-menu.update');
    Route::put('/dashboard/navigation-menus/{id}', [DashboardNavigationController::class, 'updateNavigationMenu'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('pages.view')])
        ->name('dashboard.navigation-menu.update.put');
    Route::post('/dashboard/navigation-menus/{id}/delete', [DashboardNavigationController::class, 'deleteNavigationMenu'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('pages.view')])
        ->name('dashboard.navigation-menu.delete');
    
    // Language Management
    Route::get('/dashboard/languages', [DashboardLanguageController::class, 'languages'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.languages');
    Route::get('/dashboard/languages/create', [DashboardLanguageController::class, 'createLanguage'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.language.create');
    Route::post('/dashboard/languages', [DashboardLanguageController::class, 'storeLanguage'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.language.store');
    Route::get('/dashboard/languages/{id}/edit', [DashboardLanguageController::class, 'editLanguage'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.language.edit');
    Route::post('/dashboard/languages/{id}', [DashboardLanguageController::class, 'updateLanguage'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.language.update');
    Route::put('/dashboard/languages/{id}', [DashboardLanguageController::class, 'updateLanguage'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.language.update.put');
    Route::post('/dashboard/languages/{id}/delete', [DashboardLanguageController::class, 'deleteLanguage'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.language.delete');
    Route::post('/dashboard/languages/{id}/set-default', [DashboardLanguageController::class, 'setDefaultLanguage'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.language.set-default');
    
    // Continents Management
    Route::get('/dashboard/continents', [DashboardGeoController::class, 'continents'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.continents');
    Route::get('/dashboard/continents/create', [DashboardGeoController::class, 'createContinent'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.continent.create');
    Route::post('/dashboard/continents', [DashboardGeoController::class, 'storeContinent'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.continent.store');
    Route::get('/dashboard/continents/{id}/edit', [DashboardGeoController::class, 'editContinent'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.continent.edit');
    Route::post('/dashboard/continents/{id}', [DashboardGeoController::class, 'updateContinent'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.continent.update');
    Route::put('/dashboard/continents/{id}', [DashboardGeoController::class, 'updateContinent'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.continent.update.put');
    Route::post('/dashboard/continents/{id}/delete', [DashboardGeoController::class, 'deleteContinent'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.continent.delete');
    
    // Regions Management
    Route::get('/dashboard/regions', [DashboardGeoController::class, 'regions'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.regions');
    Route::get('/dashboard/regions/create', [DashboardGeoController::class, 'createRegion'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.region.create');
    Route::post('/dashboard/regions', [DashboardGeoController::class, 'storeRegion'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.region.store');
    Route::get('/dashboard/regions/{id}/edit', [DashboardGeoController::class, 'editRegion'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.region.edit');
    Route::post('/dashboard/regions/{id}', [DashboardGeoController::class, 'updateRegion'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.region.update');
    Route::put('/dashboard/regions/{id}', [DashboardGeoController::class, 'updateRegion'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.region.update.put');
    Route::post('/dashboard/regions/{id}/delete', [DashboardGeoController::class, 'deleteRegion'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('system.languages')])
        ->name('dashboard.region.delete');
    
    // Page Management (specific routes first)
    Route::get('/dashboard/pages/create', [DashboardPageController::class, 'createPage'])->name('dashboard.page.create');
    Route::get('/dashboard/pages', [DashboardPageController::class, 'pages'])->name('dashboard.pages');
    Route::post('/dashboard/pages', [DashboardPageController::class, 'storePage'])->name('dashboard.page.store');
    Route::get('/dashboard/pages/{id}/edit', [DashboardPageController::class, 'editPage'])->where(['id' => '[0-9]+'])->name('dashboard.page.edit');
    Route::post('/dashboard/pages/{id}', [DashboardPageController::class, 'updatePage'])->where(['id' => '[0-9]+'])->name('dashboard.page.update');
    Route::put('/dashboard/pages/{id}', [DashboardPageController::class, 'updatePage'])->where(['id' => '[0-9]+'])->name('dashboard.page.update.put');
    Route::post('/dashboard/pages/{id}/delete', [DashboardPageController::class, 'deletePage'])->where(['id' => '[0-9]+'])->name('dashboard.page.delete');
    
    // Blog Post Management
    Route::get('/dashboard/blog/posts/create', [DashboardBlogController::class, 'createBlogPost'])->name('dashboard.blog.post.create');
    Route::get('/dashboard/blog/posts', [DashboardBlogController::class, 'blogPosts'])->name('dashboard.blog.posts');
    Route::post('/dashboard/blog/posts', [DashboardBlogController::class, 'storeBlogPost'])->name('dashboard.blog.post.store');
    Route::get('/dashboard/blog/posts/{id}/preview', [DashboardBlogController::class, 'previewBlogPost'])->where(['id' => '[0-9]+'])->name('dashboard.blog.post.preview');
    Route::get('/dashboard/blog/posts/{id}/edit', [DashboardBlogController::class, 'editBlogPost'])->where(['id' => '[0-9]+'])->name('dashboard.blog.post.edit');
    Route::put('/dashboard/blog/posts/{id}', [DashboardBlogController::class, 'updateBlogPost'])->where(['id' => '[0-9]+'])->name('dashboard.blog.post.update');
    Route::post('/dashboard/blog/posts/{id}/delete', [DashboardBlogController::class, 'deleteBlogPost'])->where(['id' => '[0-9]+'])->name('dashboard.blog.post.delete');
    Route::post('/dashboard/blog/upload-image', [DashboardBlogController::class, 'uploadBlogImage'])->name('dashboard.blog.upload.image');
    Route::post('/dashboard/blog/upload-featured-image', [DashboardBlogController::class, 'uploadFeaturedImage'])->name('dashboard.blog.upload.featured');
    
    // Blog Category Management
    Route::get('/dashboard/blog/categories/create', [DashboardBlogController::class, 'createBlogCategory'])->name('dashboard.blog.category.create');
    Route::get('/dashboard/blog/categories', [DashboardBlogController::class, 'blogCategories'])->name('dashboard.blog.categories');
    Route::post('/dashboard/blog/categories', [DashboardBlogController::class, 'storeBlogCategory'])->name('dashboard.blog.category.store');
    Route::get('/dashboard/blog/categories/{id}/preview', [DashboardBlogController::class, 'previewBlogCategory'])->where(['id' => '[0-9]+'])->name('dashboard.blog.category.preview');
    Route::get('/dashboard/blog/categories/{id}/edit', [DashboardBlogController::class, 'editBlogCategory'])->where(['id' => '[0-9]+'])->name('dashboard.blog.category.edit');
    Route::post('/dashboard/blog/categories/{id}', [DashboardBlogController::class, 'updateBlogCategory'])->where(['id' => '[0-9]+'])->name('dashboard.blog.category.update');
    Route::put('/dashboard/blog/categories/{id}', [DashboardBlogController::class, 'updateBlogCategory'])->where(['id' => '[0-9]+'])->name('dashboard.blog.category.update.put');
    Route::post('/dashboard/blog/categories/{id}/delete', [DashboardBlogController::class, 'deleteBlogCategory'])->where(['id' => '[0-9]+'])->name('dashboard.blog.category.delete');
    
    // Contact Messages Management
    Route::get('/dashboard/contact-messages', [DashboardContactMessageController::class, 'contactMessages'])
        ->middleware([new PermissionMiddleware('contact.view')])
        ->name('dashboard.contact.messages');
    Route::get('/dashboard/contact-messages/{id}', [DashboardContactMessageController::class, 'showContactMessage'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('contact.view')])
        ->name('dashboard.contact.message.show');
    Route::post('/dashboard/contact-messages/{id}/read', [DashboardContactMessageController::class, 'markContactMessageRead'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('contact.manage')])
        ->name('dashboard.contact.message.read');
    Route::post('/dashboard/contact-messages/{id}/replied', [DashboardContactMessageController::class, 'markContactMessageReplied'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('contact.manage')])
        ->name('dashboard.contact.message.replied');
    Route::post('/dashboard/contact-messages/{id}/delete', [DashboardContactMessageController::class, 'deleteContactMessage'])
        ->where(['id' => '[0-9]+'])
        ->middleware([new PermissionMiddleware('contact.manage')])
        ->name('dashboard.contact.message.delete');
});

// API routes with CORS
Route::prefix('api')->middleware(['cors'])->group(function() {
    // Public API endpoints
    Route::get('/status', [MainController::class, 'apiStatus'])->name('api.status');

    // Protected API endpoints
    Route::middleware(['auth'])->group(function() {
        Route::get('/user/profile', [UserController::class, 'apiProfile'])->name('api.user.profile');
    });
});

// Serve uploaded blog images (public route, no auth required)
Route::get('/storage/uploads/blog/{filename}', [DashboardBlogController::class, 'serveBlogImage'])
    ->where(['filename' => '[a-zA-Z0-9._-]+'])
    ->name('storage.blog.image');

// RESTful resource routes example
Route::get('/resource', [ResourceController::class, 'index'])->name('resource.index');
Route::get('/resource/{id}', [ResourceController::class, 'show'])->name('resource.show');
Route::post('/resource', [ResourceController::class, 'store'])->name('resource.store');
Route::put('/resource/{id}', [ResourceController::class, 'update'])->name('resource.update');
Route::delete('/resource/{id}', [ResourceController::class, 'destroy'])->name('resource.destroy');

// Optional parameter example
Route::get('/search/{query?}', [MainController::class, 'search'])->name('search');

// Contact form (authentication handled in controller)
// GET requests are public (form shown but disabled for guests)
// POST requests require authentication (handled in MainController::contact())
Route::match(['GET', 'POST'], '/contact', [MainController::class, 'contact'])
    ->middleware([new RateLimitMiddleware(3, 300)]) // 3 attempts per 5 minutes
    ->name('contact');
use App\Core\middleware\CsrfMiddleware;
