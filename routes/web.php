<?php

use App\Http\Controllers\Settings;
use App\Http\Controllers\PagesController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FacebookAuthController;
use App\Http\Controllers\DashboardController;


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

    Route::get('pages', [PagesController::class, 'index'])->name('pages');
    Route::get('/pages/{page_id}/posts',[PagesController::class,'posts'])->name('fbpages.posts');
    Route::get('/posts/{post_id}/comments', [PagesController::class, 'comments'])->name('fbpages.comments');
    Route::post('/comments/{comment_id}/reply', [PagesController::class,'replyComment'])
    ->name('fbpages.replyComment');

    Route::get('/subscription', [PagesController::class,'subscription'])
    ->name('subscription');
    Route::get('/create-subscription', [PagesController::class,'createSubscription'])
    ->name('create-subscription');
    Route::post('/facebook/webhook', [PagesController::class, 'webhookReply']);
    Route::get('/facebook/webhook', [PagesController::class,'webhook'])
    ->name('webhook');

});

require __DIR__.'/auth.php';
