<?php

use App\Http\Controllers\Settings;
use App\Http\Controllers\PagesController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FacebookAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\Admin\UserController;


Route::get('/', function () {
    return view('welcome');
})->name('home');


;



Route::get('/dashboard', [DashboardController::class,'index'])->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware(['auth'])->group(function () {
    Route::get('settings/profile', [Settings\ProfileController::class, 'edit'])->name('settings.profile.edit');
    Route::put('settings/profile', [Settings\ProfileController::class, 'update'])->name('settings.profile.update');
    Route::delete('settings/profile', [Settings\ProfileController::class, 'destroy'])->name('settings.profile.destroy');
    Route::get('settings/password', [Settings\PasswordController::class, 'edit'])->name('settings.password.edit');
    Route::put('settings/password', [Settings\PasswordController::class, 'update'])->name('settings.password.update');
    Route::get('settings/appearance', [Settings\AppearanceController::class, 'edit'])->name('settings.appearance.edit');
    Route::put('settings/appearance', [Settings\AppearanceController::class, 'update'])->name('settings.appearance.update');
    Route::get('settings/facebook', [Settings\FacebookController::class, 'edit'])->name('settings.facebook.edit');
    Route::put('settings/facebook', [Settings\FacebookController::class, 'update'])->name('settings.facebook.update');

    Route::get('pages', [PagesController::class, 'index'])
        ->middleware('permission:view-pages')
        ->name('pages');
    Route::get('/pages/{page_id}/posts',[PagesController::class,'posts'])
        ->middleware('permission:view-pages')
        ->name('fbpages.posts');
    Route::get('/posts/{post_id}/{page_id}/comments', [PagesController::class, 'comments'])
        ->middleware('permission:view-pages')
        ->name('fbpages.comments');
    Route::post('/comments/{comment_id}/reply', [PagesController::class,'replyComment'])
        ->middleware('permission:view-pages')
        ->name('fbpages.replyComment');

    Route::get('/replies/{pageId}', [PagesController::class, 'replyIndex'])
        ->middleware('permission:view-pages');
    Route::post('/replies/update', [PagesController::class, 'replyUpdate'])
        ->middleware('permission:view-pages');

    // Admin Routes for Role & Permission Management
    Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
        Route::get('/', [RolePermissionController::class, 'index'])->name('index');
        Route::get('/roles', [RolePermissionController::class, 'roles'])->name('roles');
        Route::get('/permissions', [RolePermissionController::class, 'permissions'])->name('permissions');
        Route::get('/users/{user}/roles-permissions', [RolePermissionController::class, 'userRolesPermissions'])->name('user.roles-permissions');

        // Role Management
        Route::post('/roles/create', [RolePermissionController::class, 'createRole'])->name('roles.create');
        Route::post('/roles/{role}/assign-permission', [RolePermissionController::class, 'assignPermissionToRole'])->name('roles.assign-permission');
        Route::delete('/roles/{role}/remove-permission', [RolePermissionController::class, 'removePermissionFromRole'])->name('roles.remove-permission');

        // Permission Management
        Route::post('/permissions/create', [RolePermissionController::class, 'createPermission'])->name('permissions.create');

        // User Management
        Route::post('/users/{user}/assign-role', [RolePermissionController::class, 'assignRoleToUser'])->name('user.assign-role');
        Route::post('/users/{user}/assign-permission', [RolePermissionController::class, 'assignPermissionToUser'])->name('user.assign-permission');
        Route::delete('/users/{user}/remove-role', [RolePermissionController::class, 'removeRoleFromUser'])->name('user.remove-role');
        Route::delete('/users/{user}/remove-permission', [RolePermissionController::class, 'removePermissionFromUser'])->name('user.remove-permission');
    });

    // Ticket Management
    Route::get('/tickets', [\App\Http\Controllers\Admin\TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [\App\Http\Controllers\Admin\TicketController::class, 'show'])->name('tickets.show');
    Route::put('/tickets/{ticket}', [\App\Http\Controllers\Admin\TicketController::class, 'update'])->name('tickets.update');
    Route::post('/tickets/{ticket}/assign', [\App\Http\Controllers\Admin\TicketController::class, 'assign'])->name('tickets.assign');
    Route::post('/tickets/{ticket}/close', [\App\Http\Controllers\Admin\TicketController::class, 'close'])->name('tickets.close');
    Route::post('/tickets/{ticket}/resolve', [\App\Http\Controllers\Admin\TicketController::class, 'resolve'])->name('tickets.resolve');
    Route::delete('/tickets/{ticket}', [\App\Http\Controllers\Admin\TicketController::class, 'destroy'])->name('tickets.destroy');

    

    Route::post('/replies/add', [PagesController::class, 'replyAdd'])
        ->middleware('permission:view-pages');
    Route::post('/replies/delete', [PagesController::class, 'replyDelete'])
        ->middleware('permission:view-pages');

    Route::get('/subscription', [PagesController::class,'subscription'])
        ->middleware('permission:view-subscription')
        ->name('subscription');
    Route::get('/create-subscription', [PagesController::class,'createSubscription'])
        ->middleware('permission:create-subscription')
        ->name('create-subscription');

    // Role and Permission Management (Admin only)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [RolePermissionController::class, 'index'])->name('index');

        Route::get('/roles', [RolePermissionController::class, 'getRoles'])->name('roles');
        Route::get('/permissions', [RolePermissionController::class, 'getPermissions'])->name('permissions');

        Route::get('/users/{user}/roles-permissions', [RolePermissionController::class, 'getUserRolesPermissions'])->name('user.roles-permissions');

        Route::post('/users/{user}/assign-role', [RolePermissionController::class, 'assignRole'])->name('user.assign-role');
        Route::delete('/users/{user}/remove-role', [RolePermissionController::class, 'removeRole'])->name('user.remove-role');

        Route::post('/users/{user}/grant-permission', [RolePermissionController::class, 'grantPermission'])->name('user.grant-permission');
        Route::delete('/users/{user}/revoke-permission', [RolePermissionController::class, 'revokePermission'])->name('user.revoke-permission');

        Route::post('/roles', [RolePermissionController::class, 'createRole'])->name('roles.create');
        Route::post('/permissions', [RolePermissionController::class, 'createPermission'])->name('permissions.create');

        Route::post('/roles/{role}/assign-permission', [RolePermissionController::class, 'assignPermissionToRole'])->name('role.assign-permission');
        Route::delete('/roles/{role}/remove-permission', [RolePermissionController::class, 'removePermissionFromRole'])->name('role.remove-permission');

        // User CRUD
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');


    });

});


Route::post('/facebook/webhook', [PagesController::class, 'webhookReply']);
Route::get('/facebook/webhook', [PagesController::class,'webhook'])
    ->name('webhook');

require __DIR__.'/auth.php';
