<?php

namespace App\Http\Controllers\Api\lead_service;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LeadStatusController extends Controller
{
    public function leadStatusUpdate(Request $request)
    {
        $response = Http::put(env('LEAD_SERVICE_API') . '/lead/status', ['lead_id' => $request->lead_id, 'sales_user_id' => $request->sales_user_id, 'lead_status' => $request->lead_status]);
        return response()->json(json_decode($response));
    }

    public function leadStatusLogs($lead_id){
        $response = Http::get(env('LEAD_SERVICE_API').'/lead/lead_id='.$lead_id.'/lead-status-logs');
        return response()->json(json_decode($response));
    }
}
