<?php

namespace App\Http\Controllers\Api\lead_service;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LeadListController extends Controller
{
    public function leadList(Request $request)
    {
        $lead_list = Http::post(env('LEAD_SERVICE_API', '') . '/lead/list', ['role_id' => $request->role_id, 'client_id' => $request->client_id, 'user_id' => $request->user_id]);
        return response()->json(json_decode($lead_list));
    }
}
