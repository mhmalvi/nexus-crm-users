<?php

use App\Http\Controllers\Api\ForgetPasswordController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('check-if-token-exists',[\App\Http\Controllers\Api\AuthController::class, 'token_exists']);
});

////////////// getting company id from company service to delete company credentials /////////////////
Route::post('/user/delete-company-id',[\App\Http\Controllers\Api\AuthController::class,'delete_company_id']);



Route::post('/user/register', [\App\Http\Controllers\Api\AuthController::class, 'createUser']);
Route::post('/user/update', [\App\Http\Controllers\Api\AuthController::class, 'updateUser']);


Route::post('/user/password-reset', [ForgetPasswordController::class, 'resetPassword']);
Route::post('/user/forgot-password', [ForgetPasswordController::class, 'forgotPassword']);
Route::get('reset-password/{token}', [ForgetPasswordController::class, 'showResetPasswordForm'])->name('reset.password.get');
Route::post('reset-password', [ForgetPasswordController::class, 'submitResetPasswordForm'])->name('reset.password.post');


Route::post('/user/check-verification', [\App\Http\Controllers\Api\AuthController::class, 'updateVerificationCode']);
Route::post('/user/list', [\App\Http\Controllers\Api\AuthController::class, 'userList']);
Route::get('/user/{user_id}/details', [\App\Http\Controllers\Api\AuthController::class, 'getUserDetails']);

Route::post('/user/login', [\App\Http\Controllers\Api\AuthController::class, 'loginUser']);
Route::post('/user/status', [\App\Http\Controllers\Api\AuthController::class, 'updateUserStatus']);
Route::post('/user/suspend', [\App\Http\Controllers\Api\AuthController::class, 'usersSuspend']);
Route::get('/role={role}/status={status}/fetch-user',[\App\Http\Controllers\Api\AuthController::class,'fetch_user']);
Route::post('/user/user-suspend', [\App\Http\Controllers\Api\AuthController::class, 'usersSuspendById']);

Route::post('/user/delete-user',[\App\Http\Controllers\Api\AuthController::class,'destroy_user']);

//Route::apiResource('posts', PostController::class)->middleware('auth:sanctum');

Route::get('/user/sales-list', [\App\Http\Controllers\Api\AuthController::class, 'get_sales_user']);
Route::get('/user/company_id={company_id}/sales-list-in-lead-details', [\App\Http\Controllers\Api\AuthController::class, 'fetch_sales_user_in_lead_details']);


Route::get('/follow-up', [\App\Http\Controllers\Api\ReminderController::class, 'index']);
Route::post('/follow-up', [\App\Http\Controllers\Api\ReminderController::class, 'store']);
Route::put('/follow-up-update/{id}', [\App\Http\Controllers\Api\ReminderController::class, 'update']);
Route::post('/follow-up-by-user', [\App\Http\Controllers\Api\ReminderController::class, 'show']);
Route::post('/follow-up-deactivate/{id}', [\App\Http\Controllers\Api\ReminderController::class, 'destroy']);
Route::get('/follow', [\App\Http\Controllers\Api\ReminderController::class, 'broadcast']);
Route::post('/notifications-list', [\App\Http\Controllers\Api\ReminderController::class, 'notify_list']);
Route::post('/change-status/{id}', [\App\Http\Controllers\Api\ReminderController::class, 'change_status']);
Route::get('/user-details-socket/{id}', [\App\Http\Controllers\Api\ReminderController::class, 'get_user_details']);
Route::post('/delete-notification', [\App\Http\Controllers\Api\ReminderController::class, 'destroy']);


/////////////////api used for another service////////////////
Route::get('/get-user-name',[\App\Http\Controllers\Api\AuthController::class,'get_user_name']);
Route::get('/user-details',[\App\Http\Controllers\Api\AuthController::class,'get_user_details']);
Route::post('/sales-employee-list',[\App\Http\Controllers\Api\SalesListController::class,'salesList']);