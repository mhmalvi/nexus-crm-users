<?php

namespace App\Http\Controllers\Api\lead_service;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class LeadDetailsController extends Controller
{
    public function leadDetails(Request $request)
    {
        $lead_details = Http::post(env('LEAD_SERVICE_API','') . '/lead/details', ['lead_id' => $request->lead_id]);
        return response()->json(json_decode($lead_details));
    }
}
