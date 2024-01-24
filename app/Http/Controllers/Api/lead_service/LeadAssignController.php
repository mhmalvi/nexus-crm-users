<?php

namespace App\Http\Controllers\Api\lead_service;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class LeadAssignController extends Controller
{
    public function leadAssign(Request $request)
    {
        $response = Http::crm_leads()->post('/lead/assign', ['lead_id' => $request->lead_id, 'sales_user_id' => $request->sales_user_id, 'assign_by' => $request->assign_by]);
        if ($response) {
            return response()->json(json_decode($response));
        } else {
            return response()->json([
                'message' => 'Failed',
                'status' => 500
            ], 500);
        }
    }
}
