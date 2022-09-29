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


Route::post('/user/login', [\App\Http\Controllers\Api\AuthController::class, 'loginUser']);
//Route::apiResource('posts', PostController::class)->middleware('auth:sanctum');
