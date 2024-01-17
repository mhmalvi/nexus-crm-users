<?php

namespace App\Http\Controllers\Api\lead_service;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class LeadLocationColorController extends Controller
{
    public function add_color(Request $request)
    {
        $response = Http::post('https://crmleads.queleadscrm.com/api/add-lead-location-color', ['location' => $request->location, 'color' => $request->color, 'company_id' => $request->company_id]);
        return response()->json($response);
    }
}
