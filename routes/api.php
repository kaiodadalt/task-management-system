<?php

use App\Http\Controllers\Task\ViewTaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('tasks')->group(function () {
        Route::get('/{id}', ViewTaskController::class)->name('tasks.view');
    });
});
