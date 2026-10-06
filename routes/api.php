<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\RoleController;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    $user = $request->user()->load('employee.role');

    return response()->json([
        'id' => $user->id,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'role' => $user->role,
        'avatar_url' => $user->avatar
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar)
            : null,
        'employee' => $user->employee,
    ]);
})->middleware('auth:sanctum');
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth:sanctum', 'admin']);

Route::apiResource('users', UserController::class)
    ->only(['index', 'store', 'update', 'destroy','show'])
    ->middleware(['auth:sanctum', 'admin']);

Route::apiResource('roles', RoleController::class)
    ->only(['index', 'store', 'show', 'update', 'destroy'])
    ->middleware(['auth:sanctum', 'admin']);

Route::apiResource('employees', EmployeeController::class)
    ->only(['index', 'store', 'show', 'update', 'destroy'])
    ->middleware(['auth:sanctum', 'admin']);

Route::patch('/employees/{employee}/archive', [EmployeeController::class, 'archive'])
    ->middleware(['auth:sanctum', 'admin']);

Route::patch('/employees/{employee}/restore', [EmployeeController::class, 'restore'])
    ->middleware(['auth:sanctum', 'admin']);

Route::patch('/profile', [AuthController::class, 'updateProfile'])
    ->middleware('auth:sanctum');

Route::post('/profile/avatar', [AuthController::class, 'updateAvatar'])
    ->middleware('auth:sanctum');

Route::apiResource('clients', ClientController::class)
    ->middleware(['auth:sanctum', 'admin']);

Route::patch('/clients/{client}/archive', [ClientController::class, 'archive'])
    ->middleware(['auth:sanctum', 'admin']);

Route::patch('/clients/{client}/restore', [ClientController::class, 'restore'])
    ->middleware(['auth:sanctum', 'admin']);

Route::apiResource('projects', ProjectController::class)
    ->middleware(['auth:sanctum', 'admin']);

Route::patch('/projects/{project}/archive', [ProjectController::class, 'archive'])
    ->middleware(['auth:sanctum', 'admin']);

Route::patch('/projects/{project}/restore', [ProjectController::class, 'restore'])
    ->middleware(['auth:sanctum', 'admin']);
Route::get('/task-projects', function () {
    return \App\Models\Project::query()
        ->select('id', 'name')
        ->whereNull('archived_at')
        ->get();
})->middleware(['auth:sanctum', 'task.access']);


Route::apiResource('tasks', TaskController::class)
    ->middleware('auth:sanctum', 'task.access');

Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'admin']);

Route::patch('/tasks/{task}/archive', [TaskController::class, 'archive', 'admin']);

Route::patch('/tasks/{task}/restore', [TaskController::class, 'restore', 'admin']);
Route::get('/task-users', function () {
    return \App\Models\User::query()
        ->select('id', 'first_name', 'last_name')
        ->get();
})->middleware(['auth:sanctum', 'task.access']);

Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/search', function (Request $request) {
    $query = $request->string('q')->trim();
    if ($query->isEmpty()) {
        return response()->json([
            'clients' => [],
            'projects' => [],
            'tasks' => [],
        ]);
    }

    return response()->json([
        'clients' => Client::where(function ($clientQuery) use ($query) {
            $clientQuery
                ->where('first_name', 'like', "%{$query}%")
                ->orWhere('last_name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('phone', 'like', "%{$query}%");
        })
            ->whereNull('archived_at')
            ->limit(5)
            ->get([
                'id',
                'first_name',
                'last_name',
                'email',
                'phone',
            ]),

        'projects' => Project::where('name', 'like', "%{$query}%")
            ->whereNull('archived_at')
            ->limit(5)
            ->get(['id', 'name']),

        'tasks' => Task::where('title', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'title']),
    ]);
})->middleware('auth:sanctum');
