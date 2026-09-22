<?php

use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\Assessment\AssessmentController;
use App\Http\Controllers\Assessment\AssessmentReportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReviewController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : view('welcome'))
    ->name('home');
Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])
        ->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:registration');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])
        ->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.update');
});
Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', fn () => view('auth.verify-email'))
        ->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request): RedirectResponse {
        $request->fulfill();

        return redirect()->route('dashboard')->with('status', 'Your email address has been verified.');
    })->middleware(['signed', 'throttle:verification'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', function (Request $request): RedirectResponse {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'A new verification link has been sent.');
    })->middleware('throttle:verification')
        ->name('verification.send');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    Route::get('/assessments', [AssessmentController::class, 'index'])
        ->name('assessments.index');
    Route::post('/assessments', [AssessmentController::class, 'create'])
        ->middleware('throttle:sensitive')
        ->name('assessments.create');
    Route::get('/assessments/{assessment}', [AssessmentController::class, 'show'])
        ->name('assessments.show');
    Route::put('/assessments/{assessment}', [AssessmentController::class, 'save'])
        ->middleware('throttle:sensitive')
        ->name('assessments.save');
    Route::get('/assessments/{assessment}/report', [AssessmentReportController::class, 'download'])
        ->name('assessments.report');
    Route::post('/assessments/{assessment}/reviews', [ReviewController::class, 'request'])
        ->middleware('throttle:sensitive')
        ->name('reviews.request');
    Route::get('/reviews', [ReviewController::class, 'index'])
        ->name('reviews.index');
    Route::get('/reviews/{review}/responses', [ReviewController::class, 'responses'])
        ->name('reviews.responses');
    Route::get('/reviews/{review}', [ReviewController::class, 'show'])
        ->name('reviews.show');
    Route::patch('/reviews/{review}', [ReviewController::class, 'update'])
        ->middleware('throttle:sensitive')
        ->name('reviews.update');
    Route::post('/reviews/{review}/comments', [ReviewController::class, 'comment'])
        ->middleware('throttle:sensitive')
        ->name('reviews.comments');
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/users', [UserRoleController::class, 'index'])
            ->name('users.index');
        Route::patch('/users/{user}/role', [UserRoleController::class, 'update'])
            ->middleware('throttle:sensitive')
            ->name('users.role.update');
    });
});
