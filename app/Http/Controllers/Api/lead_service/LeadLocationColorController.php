<?php

namespace App\Http\Controllers\Api\lead_service;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class LeadLocationColorController extends Controller
{
    public function add_color(Request $request)
    {
        $request->validate([
            'location' => 'required',
            'color' => 'required',
            'company_id' => 'required'
        ]);
        $response = Http::post('https://crmleads.queleadscrm.com/api/add-lead-location-color', ['location' => $request->location, 'color' => $request->color, 'company_id' => $request->company_id]);
        return response()->json(json_decode($response));
    }

    public function getColor(Request $request, $company_id)
    {

        if ($company_id) {
            $response = Http::post('https://crmleads.queleadscrm.com/api/location-color', ['company_id' => $company_id]);
            return response()->json($response);
        } else {
            return response()->json([
                'message' => 'Please provide company id',
                'status' => 500
            ], 500);
        }
    }
}
