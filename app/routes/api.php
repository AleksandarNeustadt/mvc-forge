<?php
// routes/api.php
// API routes for content management

use App\Controllers\ApiAuthController;
use App\Controllers\ApiCategoryController;
use App\Controllers\ApiLanguageController;
use App\Controllers\ApiMenuController;
use App\Controllers\ApiPageController;
use App\Controllers\ApiPostController;
use App\Controllers\ApiTagController;

// CORS middleware for API
// Rate limiting: 1000 requests per 60 seconds for authenticated API users (generous limit for bulk operations)
Route::prefix('api')->middleware(['cors', new RateLimitMiddleware(1000, 60)])->group(function() {
    
    // ========== Authentication Routes (Public) ==========
    // Login has stricter rate limit: 10 attempts per 60 seconds
    Route::post('/auth/login', [ApiAuthController::class, 'login'])
        ->middleware([new RateLimitMiddleware(10, 60)])
        ->name('api.auth.login');
    Route::post('/auth/logout', [ApiAuthController::class, 'logout'])
        ->middleware([new ApiAuthMiddleware()])
        ->name('api.auth.logout');
    
    // ========== Protected API Routes ==========
    Route::middleware([new ApiAuthMiddleware()])->group(function() {
        
        // User info
        Route::get('/auth/me', [ApiAuthController::class, 'me'])->name('api.auth.me');
        
        // ========== Pages ==========
        Route::get('/pages', [ApiPageController::class, 'listPages'])->name('api.pages.list');
        Route::get('/pages/{id}', [ApiPageController::class, 'getPage'])
            ->where(['id' => '[0-9]+'])
            ->name('api.pages.get');
        Route::post('/pages', [ApiPageController::class, 'createPage'])->name('api.pages.create');
        Route::put('/pages/{id}', [ApiPageController::class, 'updatePage'])
            ->where(['id' => '[0-9]+'])
            ->name('api.pages.update');
        Route::delete('/pages/{id}', [ApiPageController::class, 'deletePage'])
            ->where(['id' => '[0-9]+'])
            ->name('api.pages.delete');
        
        // ========== Menus ==========
        Route::get('/menus', [ApiMenuController::class, 'listMenus'])->name('api.menus.list');
        Route::get('/menus/{id}', [ApiMenuController::class, 'getMenu'])
            ->where(['id' => '[0-9]+'])
            ->name('api.menus.get');
        Route::post('/menus', [ApiMenuController::class, 'createMenu'])->name('api.menus.create');
        Route::put('/menus/{id}', [ApiMenuController::class, 'updateMenu'])
            ->where(['id' => '[0-9]+'])
            ->name('api.menus.update');
        Route::delete('/menus/{id}', [ApiMenuController::class, 'deleteMenu'])
            ->where(['id' => '[0-9]+'])
            ->name('api.menus.delete');
        
        // ========== Posts ==========
        Route::get('/posts', [ApiPostController::class, 'listPosts'])->name('api.posts.list');
        Route::get('/posts/{id}', [ApiPostController::class, 'getPost'])
            ->where(['id' => '[0-9]+'])
            ->name('api.posts.get');
        Route::post('/posts', [ApiPostController::class, 'createPost'])->name('api.posts.create');
        Route::post('/posts/bulk', [ApiPostController::class, 'bulkCreatePosts'])->name('api.posts.bulk');
        Route::put('/posts/{id}', [ApiPostController::class, 'updatePost'])
            ->where(['id' => '[0-9]+'])
            ->name('api.posts.update');
        Route::delete('/posts/{id}', [ApiPostController::class, 'deletePost'])
            ->where(['id' => '[0-9]+'])
            ->name('api.posts.delete');
        
        // ========== Categories ==========
        Route::get('/categories', [ApiCategoryController::class, 'listCategories'])->name('api.categories.list');
        Route::get('/categories/{id}', [ApiCategoryController::class, 'getCategory'])
            ->where(['id' => '[0-9]+'])
            ->name('api.categories.get');
        Route::post('/categories', [ApiCategoryController::class, 'createCategory'])->name('api.categories.create');
        Route::put('/categories/{id}', [ApiCategoryController::class, 'updateCategory'])
            ->where(['id' => '[0-9]+'])
            ->name('api.categories.update');
        Route::delete('/categories/{id}', [ApiCategoryController::class, 'deleteCategory'])
            ->where(['id' => '[0-9]+'])
            ->name('api.categories.delete');
        
        // ========== Tags ==========
        Route::get('/tags', [ApiTagController::class, 'listTags'])->name('api.tags.list');
        Route::get('/tags/{id}', [ApiTagController::class, 'getTag'])
            ->where(['id' => '[0-9]+'])
            ->name('api.tags.get');
        Route::post('/tags', [ApiTagController::class, 'createTag'])->name('api.tags.create');
        Route::put('/tags/{id}', [ApiTagController::class, 'updateTag'])
            ->where(['id' => '[0-9]+'])
            ->name('api.tags.update');
        Route::delete('/tags/{id}', [ApiTagController::class, 'deleteTag'])
            ->where(['id' => '[0-9]+'])
            ->name('api.tags.delete');
        
        // ========== Languages ==========
        Route::get('/languages', [ApiLanguageController::class, 'listLanguages'])->name('api.languages.list');
        Route::get('/languages/{id}', [ApiLanguageController::class, 'getLanguage'])
            ->where(['id' => '[0-9]+'])
            ->name('api.languages.get');
        Route::get('/languages/code/{code}', [ApiLanguageController::class, 'getLanguageByCode'])
            ->where(['code' => '[a-z]{2}'])
            ->name('api.languages.getByCode');
        Route::post('/languages', [ApiLanguageController::class, 'createLanguage'])->name('api.languages.create');
        Route::put('/languages/{id}', [ApiLanguageController::class, 'updateLanguage'])
            ->where(['id' => '[0-9]+'])
            ->name('api.languages.update');
        Route::delete('/languages/{id}', [ApiLanguageController::class, 'deleteLanguage'])
            ->where(['id' => '[0-9]+'])
            ->name('api.languages.delete');
    });
});

