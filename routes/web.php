<?php

use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('welcome');
})->name('home');
Route::middleware('guest')->group(function (): void { Route::get('/register', [RegisteredUserController::class, 'create'])->name('register'); Route::post('/register', [RegisteredUserController::class, 'store']); Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login'); Route::post('/login', [AuthenticatedSessionController::class, 'store']); });
Route::middleware('auth')->group(function (): void { Route::view('/dashboard', 'dashboard')->name('dashboard'); Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout'); Route::middleware('admin')->prefix('admin')->name('admin.')->group(function (): void { Route::get('/users', [UserRoleController::class, 'index'])->name('users.index'); Route::patch('/users/{user}/role', [UserRoleController::class, 'update'])->name('users.role.update'); }); });
