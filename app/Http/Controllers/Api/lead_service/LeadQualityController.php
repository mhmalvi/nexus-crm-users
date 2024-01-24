<?php

namespace App\Http\Controllers\Api\lead_service;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LeadQualityController extends Controller
{
    public function leadQualityUpdate(Request $request)
    {
        $response = Http::crm_leads()->put('/lead/quality/update', ['lead_id' => $request->lead_id, 'sales_user_id' => $request->sales_user_id, 'star_review' => $request->star_review]);
        if ($response) {
            return response()->json($response);
        } else {
            return response()->json([
                'message' => 'failed',
                'status' => 500
            ], 500);
        }
    }
}
