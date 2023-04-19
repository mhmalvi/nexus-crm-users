<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/user/register', [\App\Http\Controllers\Api\AuthController::class, 'createUser']);
Route::post('/user/update', [\App\Http\Controllers\Api\AuthController::class, 'updateUser']);
Route::post('/user/password-reset', [\App\Http\Controllers\Api\AuthController::class, 'resetPassword']);
Route::post('/user/forgot-password', [\App\Http\Controllers\Api\AuthController::class, 'forgotPassword']);
Route::post('/user/check-verification', [\App\Http\Controllers\Api\AuthController::class, 'updateVerificationCode']);
Route::post('/user/list', [\App\Http\Controllers\Api\AuthController::class, 'userList']);
Route::get('/user/{user_id}/details', [\App\Http\Controllers\Api\AuthController::class, 'getUserDetails']);

Route::post('/user/login', [\App\Http\Controllers\Api\AuthController::class, 'loginUser']);
Route::post('/user/status', [\App\Http\Controllers\Api\AuthController::class, 'updateUserStatus']);
Route::post('/user/suspend', [\App\Http\Controllers\Api\AuthController::class, 'usersSuspend']);
Route::post('/user/user-suspend', [\App\Http\Controllers\Api\AuthController::class, 'usersSuspendById']);
//Route::apiResource('posts', PostController::class)->middleware('auth:sanctum');


Route::get('/follow-up', [\App\Http\Controllers\Api\ReminderController::class, 'index']);
Route::post('/follow-up', [\App\Http\Controllers\Api\ReminderController::class, 'store']);
Route::put('/follow-up-update/{id}', [\App\Http\Controllers\Api\ReminderController::class, 'update']);
Route::post('/follow-up-by-user', [\App\Http\Controllers\Api\ReminderController::class, 'show']);
Route::post('/follow-up-deactivate/{id}', [\App\Http\Controllers\Api\ReminderController::class, 'destroy']);