<?php

use App\Http\Controllers\Task\CreateTaskController;
use App\Http\Controllers\Task\UpdateTaskController;
use App\Http\Controllers\Task\ViewTaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('tasks')->group(function () {
        Route::post('/', CreateTaskController::class)->name('tasks.create');
        Route::get('/{task}', ViewTaskController::class)->name('tasks.view');
        Route::put('/{task}', UpdateTaskController::class)->name('tasks.update');
    });
});
