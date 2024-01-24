<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\lead_service\LeadListController;
use App\Http\Controllers\Api\lead_service\LeadStatusController;
use App\Http\Controllers\Api\lead_service\LeadDetailsController;
use App\Http\Controllers\Api\lead_service\LeadLocationColorController;
use App\Http\Controllers\Api\lead_service\LeadMultiCommentController;
use App\Http\Controllers\Api\lead_service\LeadQualityController;
use App\Http\Controllers\Api\lead_service\LeadResponseController;
use App\Http\Controllers\Api\lead_service\LeadReviewController;
use App\Http\Controllers\Api\lead_service\LeadSingleCommentController;

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
    Route::post('lead/list', [LeadListController::class, 'leadList']);
    Route::post('lead/details', [LeadDetailsController::class, 'leadDetails']);
    Route::put('lead/status', [LeadStatusController::class, 'leadStatusUpdate']);
    Route::get('lead/lead_id={lead_id}/lead-status-logs', [LeadStatusController::class, 'leadStatusLogs']);
    Route::post('review/{lead_id}', [LeadSingleCommentController::class, 'single_comment']);
    Route::put('/lead/response', [LeadResponseController::class, 'leadResponse']);
    Route::put('/lead/quality/update', [LeadQualityController::class, 'leadQualityUpdate']);
    Route::post('/multi-review/{lead_id}', [LeadMultiCommentController::class, 'multi_comment']);
    
    Route::post('add-lead-location-color', [LeadLocationColorController::class, 'add_color']);
    Route::get('{company_id}/location-color', [LeadLocationColorController::class, 'getColor']);
    Route::get('delete-location-color', [LeadLocationColorController::class, 'deleteColor']);
    Route::put('update-location-color', [LeadLocationColorController::class, 'updateColor']);

});
