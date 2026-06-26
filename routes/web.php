<?php

use App\Http\Controllers\AdminRecordController;
use App\Http\Controllers\AdminPermissionController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminTaskController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MatrixController;
use App\Http\Controllers\SpecialDayController;
use App\Http\Controllers\TaskFileController;
use App\Http\Controllers\TechnicianPanelController;
use App\Http\Controllers\UserPanelController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.post');
Route::post('/recuperar-contrasena/buscar', [AuthController::class, 'searchPasswordRecovery'])->middleware('throttle:password-recovery')->name('password.recovery.search');
Route::post('/recuperar-contrasena/token', [AuthController::class, 'requestPasswordRecoveryToken'])->middleware('throttle:password-recovery')->name('password.recovery.token');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('throttle:internal')->name('logout');

Route::middleware(['role:admin', 'throttle:internal'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'admin'])->name('dashboard');
    Route::get('/usuarios-en-linea', [DashboardController::class, 'onlineUsers'])->name('users.online');
    Route::get('/configurar-admin-base', [AdminUserController::class, 'baseAdminSetup'])->name('base-admin.setup');
    Route::patch('/configurar-admin-base', [AdminUserController::class, 'updateBaseAdminSetup'])->name('base-admin.update');
    Route::get('/mi-perfil', [UserPanelController::class, 'profile'])->name('profile');
    Route::post('/mi-perfil/foto', [UserPanelController::class, 'updateProfilePhoto'])->name('profile.photo.update');
    Route::delete('/mi-perfil/foto', [UserPanelController::class, 'deleteProfilePhoto'])->name('profile.photo.destroy');
    Route::get('/mis-tareas', [UserPanelController::class, 'tasks'])->name('my-tasks.index');
    Route::get('/subir-tareas', [UserPanelController::class, 'uploadTasks'])->name('my-tasks.upload.index');
    Route::get('/mis-tareas/{task}', [UserPanelController::class, 'showTask'])->name('my-tasks.show');
    Route::post('/mis-tareas/{task}/preparar-archivo', [UserPanelController::class, 'prepareUpload'])->name('my-tasks.upload.prepare');
    Route::delete('/mis-tareas/{task}/archivos-preparados', [UserPanelController::class, 'clearPreparedUploads'])->name('my-tasks.upload.prepared.clear');
    Route::post('/mis-tareas/{task}/subir', [UserPanelController::class, 'upload'])->name('my-tasks.upload');
    Route::delete('/mis-tareas/{task}/archivos/{file}', [UserPanelController::class, 'deleteFile'])->name('my-tasks.files.destroy');
    Route::delete('/mis-tareas/{task}/archivo-principal', [UserPanelController::class, 'deleteLegacyFile'])->name('my-tasks.files.legacy-destroy');
    Route::resource('records', AdminRecordController::class)->except(['show']);

    Route::get('users/history', [AdminUserController::class, 'history'])->name('users.history');
    Route::resource('users', AdminUserController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::get('users/{user}', [AdminUserController::class, 'show'])->withTrashed()->name('users.show');
    Route::post('users/{user}/recuperar-contrasena', [AdminUserController::class, 'recoverPassword'])->name('users.password.recover');
    Route::post('users/{user}/permisos-admin/conceder', [AdminPermissionController::class, 'grant'])->name('users.admin-permissions.grant');
    Route::post('users/{user}/permisos-admin/cancelar', [AdminPermissionController::class, 'revoke'])->name('users.admin-permissions.revoke');
    Route::patch('permisos-admin/solicitudes/{adminPermissionRequest}/denegar', [AdminPermissionController::class, 'deny'])->name('admin-permissions.deny');

    Route::get('matrix/{filter?}', [MatrixController::class, 'index'])->name('matrix.index');
    Route::patch('matrix/{record}/cumplimiento', [MatrixController::class, 'updateCompliance'])->name('matrix.updateCompliance');
    Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::match(['get', 'post'], 'reports/semanal', [AdminReportController::class, 'weekly'])->name('reports.weekly');
    Route::match(['get', 'post'], 'reports/fechas', [AdminReportController::class, 'dateRange'])->name('reports.dates');
    Route::match(['get', 'post'], 'tasks/{task}/reporte', [AdminReportController::class, 'task'])->name('tasks.report');
    Route::get('tasks/{task}', [AdminTaskController::class, 'show'])->name('tasks.show');

    Route::resource('special-days', SpecialDayController::class)->except(['show']);
});

Route::middleware(['role:admin,user,tecnico', 'throttle:internal'])->prefix('archivos-tarea')->name('task-files.')->group(function () {
    Route::get('{file}/ver', [TaskFileController::class, 'show'])->name('show');
    Route::get('{file}/descargar', [TaskFileController::class, 'download'])->name('download');
    Route::get('legado/{task}/ver', [TaskFileController::class, 'showLegacy'])->name('legacy.show');
    Route::get('legado/{task}/descargar', [TaskFileController::class, 'downloadLegacy'])->name('legacy.download');
});

Route::middleware(['role:user', 'throttle:internal'])->prefix('usuario')->name('user.')->group(function () {
    Route::get('/', [UserPanelController::class, 'dashboard'])->name('dashboard');
    Route::get('/usuarios-en-linea', [DashboardController::class, 'onlineUsers'])->name('users.online');
    Route::get('/mis-tareas', [UserPanelController::class, 'tasks'])->name('tasks');
    Route::get('/subir-tareas', [UserPanelController::class, 'uploadTasks'])->name('tasks.upload.index');
    Route::get('/mis-tareas/{task}', [UserPanelController::class, 'showTask'])->name('tasks.show');
    Route::post('/mis-tareas/{task}/preparar-archivo', [UserPanelController::class, 'prepareUpload'])->name('tasks.upload.prepare');
    Route::delete('/mis-tareas/{task}/archivos-preparados', [UserPanelController::class, 'clearPreparedUploads'])->name('tasks.upload.prepared.clear');
    Route::post('/mis-tareas/{task}/subir', [UserPanelController::class, 'upload'])->name('tasks.upload');
    Route::delete('/mis-tareas/{task}/archivos/{file}', [UserPanelController::class, 'deleteFile'])->name('tasks.files.destroy');
    Route::delete('/mis-tareas/{task}/archivo-principal', [UserPanelController::class, 'deleteLegacyFile'])->name('tasks.files.legacy-destroy');
    Route::get('/mi-perfil', [UserPanelController::class, 'profile'])->name('profile');
    Route::post('/mi-perfil/foto', [UserPanelController::class, 'updateProfilePhoto'])->name('profile.photo.update');
    Route::delete('/mi-perfil/foto', [UserPanelController::class, 'deleteProfilePhoto'])->name('profile.photo.destroy');
});

Route::middleware(['role:tecnico', 'throttle:internal'])->prefix('tecnico')->name('tecnico.')->group(function () {
    Route::get('/', [TechnicianPanelController::class, 'dashboard'])->name('dashboard');
    Route::get('/usuarios-en-linea', [DashboardController::class, 'onlineUsers'])->name('users.online');
    Route::get('/matriz', [TechnicianPanelController::class, 'matrix'])->name('matrix');
    Route::get('/usuarios', [TechnicianPanelController::class, 'users'])->name('users');
    Route::get('/usuarios/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::post('/usuarios/{user}/recuperar-contrasena', [AdminUserController::class, 'recoverPassword'])->name('users.password.recover');
    Route::get('/mis-tareas', [UserPanelController::class, 'tasks'])->name('tasks');
    Route::get('/subir-tareas', [UserPanelController::class, 'uploadTasks'])->name('tasks.upload.index');
    Route::get('/mis-tareas/{task}', [UserPanelController::class, 'showTask'])->name('tasks.show');
    Route::post('/mis-tareas/{task}/preparar-archivo', [UserPanelController::class, 'prepareUpload'])->name('tasks.upload.prepare');
    Route::delete('/mis-tareas/{task}/archivos-preparados', [UserPanelController::class, 'clearPreparedUploads'])->name('tasks.upload.prepared.clear');
    Route::post('/mis-tareas/{task}/subir', [UserPanelController::class, 'upload'])->name('tasks.upload');
    Route::delete('/mis-tareas/{task}/archivos/{file}', [UserPanelController::class, 'deleteFile'])->name('tasks.files.destroy');
    Route::delete('/mis-tareas/{task}/archivo-principal', [UserPanelController::class, 'deleteLegacyFile'])->name('tasks.files.legacy-destroy');
    Route::get('/mi-perfil', [UserPanelController::class, 'profile'])->name('profile');
    Route::post('/mi-perfil/foto', [UserPanelController::class, 'updateProfilePhoto'])->name('profile.photo.update');
    Route::delete('/mi-perfil/foto', [UserPanelController::class, 'deleteProfilePhoto'])->name('profile.photo.destroy');
    Route::post('/mi-perfil/permisos-admin/solicitar', [AdminPermissionController::class, 'request'])->name('admin-permissions.request');
    Route::delete('/mi-perfil/permisos-admin/cancelar', [AdminPermissionController::class, 'cancel'])->name('admin-permissions.cancel');
});
