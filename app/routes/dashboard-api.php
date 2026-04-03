<?php
// routes/dashboard-api.php
// RESTful API routes for dashboard CRUD operations

use App\Controllers\DashboardApiController;
use App\Core\middleware\CsrfMiddleware;

// All dashboard API routes require authentication
Route::prefix('api/dashboard')->middleware(['auth', new CsrfMiddleware()])->group(function() {
    
    // ========== Standard CRUD Routes ==========
    
    // List all resources for an app
    // GET /api/dashboard/{app}
    Route::get('/{app}', [DashboardApiController::class, 'index'])
        ->where(['app' => '[a-z0-9-]+'])
        ->name('dashboard.api.index');
    
    // Show single resource
    // GET /api/dashboard/{app}/{id}/show
    Route::get('/{app}/{id}/show', [DashboardApiController::class, 'show'])
        ->where(['app' => '[a-z0-9-]+', 'id' => '[0-9]+'])
        ->name('dashboard.api.show');
    
    // Create new resource
    // POST /api/dashboard/{app}/create
    Route::post('/{app}/create', [DashboardApiController::class, 'create'])
        ->where(['app' => '[a-z0-9-]+'])
        ->name('dashboard.api.create');
    
    // Update resource
    // POST /api/dashboard/{app}/{id}/update
    Route::post('/{app}/{id}/update', [DashboardApiController::class, 'update'])
        ->where(['app' => '[a-z0-9-]+', 'id' => '[0-9]+'])
        ->name('dashboard.api.update');
    
    // Alternative: PUT /api/dashboard/{app}/{id}
    Route::put('/{app}/{id}', [DashboardApiController::class, 'update'])
        ->where(['app' => '[a-z0-9-]+', 'id' => '[0-9]+'])
        ->name('dashboard.api.update.put');
    
    // Delete resource
    // DELETE /api/dashboard/{app}/{id}/delete
    Route::delete('/{app}/{id}/delete', [DashboardApiController::class, 'delete'])
        ->where(['app' => '[a-z0-9-]+', 'id' => '[0-9]+'])
        ->name('dashboard.api.delete');
    
    // ========== Filter Options Route ==========
    // Get dynamic filter options based on current filters
    // GET /api/dashboard/filter-options/{filterType}?language_id=1&continent_id=2
    Route::get('/filter-options/{filterType}', [DashboardApiController::class, 'getFilterOptions'])
        ->where(['filterType' => '[a-z-]+'])
        ->name('dashboard.api.filter-options');
    
    // ========== Custom Actions Routes ==========
    // These routes handle app-specific actions like ban, unban, etc.
    
    // POST /api/dashboard/{app}/{id}/{action}
    // Examples:
    //   POST /api/dashboard/users/1/ban
    //   POST /api/dashboard/users/1/unban
    //   POST /api/dashboard/users/1/approve
    Route::post('/{app}/{id}/{action}', [DashboardApiController::class, 'action'])
        ->where(['app' => '[a-z0-9-]+', 'id' => '[0-9]+', 'action' => '[a-z0-9-]+'])
        ->name('dashboard.api.action');
});
