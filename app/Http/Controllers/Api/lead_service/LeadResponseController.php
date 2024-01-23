<?php

namespace App\Http\Controllers\Api\lead_service;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class LeadResponseController extends Controller
{
    public function leadResponse(Request $request)
    {
        $request->validate([
            'lead_id' => 'required',
            'lead_status' => 'required',
            'response' => 'required',
            'client_id'=>'required'
        ]);
        $response = Http::crm_leads()->put('/lead/response', ['lead_id' => $request->lead_id, 'lead_status' => $request->lead_status, 'response' => $request->response, 'keyword' => 'lead', 'client_id' => $request->client_id]);
        if ($response) {
            return response()->json(json_decode($response));
        } else {
            return response()->json([
                'message' => "failed",
                'status' => 500
            ]);
        }
    }
}
