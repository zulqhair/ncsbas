<?php

use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\Assessment\AssessmentController;
use App\Http\Controllers\Assessment\AssessmentReportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : view('welcome'))
    ->name('home');
Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])
        ->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});
Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    Route::get('/assessments', [AssessmentController::class, 'index'])
        ->name('assessments.index');
    Route::post('/assessments', [AssessmentController::class, 'create'])
        ->name('assessments.create');
    Route::get('/assessments/{assessment}', [AssessmentController::class, 'show'])
        ->name('assessments.show');
    Route::put('/assessments/{assessment}', [AssessmentController::class, 'save'])
        ->name('assessments.save');
    Route::get('/assessments/{assessment}/report', [AssessmentReportController::class, 'download'])
        ->name('assessments.report');
    Route::post('/assessments/{assessment}/reviews', [ReviewController::class, 'request'])
        ->name('reviews.request');
    Route::get('/reviews', [ReviewController::class, 'index'])
        ->name('reviews.index');
    Route::get('/reviews/{review}', [ReviewController::class, 'show'])
        ->name('reviews.show');
    Route::patch('/reviews/{review}', [ReviewController::class, 'update'])
        ->name('reviews.update');
    Route::post('/reviews/{review}/comments', [ReviewController::class, 'comment'])
        ->name('reviews.comments');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/users', [UserRoleController::class, 'index'])
            ->name('users.index');
        Route::patch('/users/{user}/role', [UserRoleController::class, 'update'])
            ->name('users.role.update');
    });
});
