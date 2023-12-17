<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class SalesListController extends Controller
{
    public function salesList(Request $request)
    {

        if (!isset($request->users)) {
            return response()->json([
                'status' => false,
                'message' => 'User id required'
            ], 406);
        }
        $userIdArray = json_decode($request->users);
            if ($request->role == 5) {
                $data = User::leftJoin('user_profile', function ($join) {
                    $join->on('user_profile.user_id', '=', 'users.id');
                })->whereIn('users.id', $userIdArray)
                    ->where('users.status', 1)->where('role_id', 5)
                    ->get();
                    if($data){
                        return response()->json($data);
                    }
            } 
            // else {
            //     $data = User::leftJoin('user_profile', function ($join) {
            //         $join->on('user_profile.user_id', '=', 'users.id');
            //     })->whereIn('users.id', $userIdArray)
            //         ->where('users.status', 1)
            //         ->get();
            // }
            
    }
}
