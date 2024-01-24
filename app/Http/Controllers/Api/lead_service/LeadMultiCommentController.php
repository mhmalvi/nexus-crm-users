<?php

namespace App\Http\Controllers\Api\lead_service;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class LeadMultiCommentController extends Controller
{
    public function multi_comment(Request $request, $lead_id)
    {
        $response = Http::crm_leads()->post('/multi-review/' . $lead_id, ['comments' => $request->comments]);
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
