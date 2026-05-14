<?php

use App\Http\Controllers\AdminRecordController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MatrixController;
use App\Http\Controllers\SpecialDayController;
use App\Http\Controllers\TechnicianPanelController;
use App\Http\Controllers\UserPanelController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'admin'])->name('dashboard');
    Route::resource('records', AdminRecordController::class)->except(['show']);

    Route::get('users/history', [AdminUserController::class, 'history'])->name('users.history');
    Route::resource('users', AdminUserController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    Route::get('matrix/{filter?}', [MatrixController::class, 'index'])->name('matrix.index');
    Route::patch('matrix/{record}/cumplimiento', [MatrixController::class, 'updateCompliance'])->name('matrix.updateCompliance');

    Route::resource('special-days', SpecialDayController::class)->except(['show']);
});

Route::middleware('role:user')->prefix('usuario')->name('user.')->group(function () {
    Route::get('/', [UserPanelController::class, 'dashboard'])->name('dashboard');
    Route::get('/mis-tareas', [UserPanelController::class, 'tasks'])->name('tasks');
    Route::get('/mis-tareas/{task}', [UserPanelController::class, 'showTask'])->name('tasks.show');
    Route::post('/mis-tareas/{task}/subir', [UserPanelController::class, 'upload'])->name('tasks.upload');
    Route::get('/mi-perfil', [UserPanelController::class, 'profile'])->name('profile');
});

Route::middleware('role:tecnico')->prefix('tecnico')->name('tecnico.')->group(function () {
    Route::get('/', [TechnicianPanelController::class, 'dashboard'])->name('dashboard');
    Route::get('/matriz', [TechnicianPanelController::class, 'matrix'])->name('matrix');
    Route::get('/usuarios', [TechnicianPanelController::class, 'users'])->name('users');
    Route::get('/mis-tareas', [UserPanelController::class, 'tasks'])->name('tasks');
    Route::get('/mis-tareas/{task}', [UserPanelController::class, 'showTask'])->name('tasks.show');
    Route::post('/mis-tareas/{task}/subir', [UserPanelController::class, 'upload'])->name('tasks.upload');
    Route::get('/mi-perfil', [UserPanelController::class, 'profile'])->name('profile');
});
