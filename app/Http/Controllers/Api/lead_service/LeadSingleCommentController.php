<?php

namespace App\Http\Controllers\Api\lead_service;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class LeadSingleCommentController extends Controller
{
    public function single_comment(Request $request, $lead_id)
    {
        if ($lead_id) {
            $response = Http::post(env('LEAD_SERVICE_API') . '/review/' . $lead_id);
            return response()->json($response);
        } else {
            return response()->json([
                'message' => 'No lead id'
            ], 404);
        }
    }
}
