<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ForgetPasswordController extends Controller
{
    public function forgotPassword(Request $request)
    {

        $request->validate([
            'email' => 'required|email|exists:users',
        ]);
        $token = Str::random(64);

        DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);

        Mail::send('email.forgetpassword', ['token' => $token], function ($message) use ($request) {
            $message->to($request->email);
            $message->subject('Reset Password');
        });
        return response()->json([
            'message' => 'success',
            'status' => 200
        ], 200);
    }

    public function showResetPasswordForm($token)
    {
        return view('auth.forgetPasswordLink', ['token' => $token]);
    }

    public function submitResetPasswordForm(Request $request)
    {
        // $rules = [
        //     'email'    => 'required|email|exists:App\Models\User,email',
        //     'password' => 'required|alphaNum|min:8'
        // ];

        // $validator = Validator::make($request->all(), $rules);
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required'
        ]);
        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator);
            // return response()->json([
            //     'message' => 'Email is not valid',
            //     'status' => 500,
            //     'data'=> $validator
            // ]);
        }

        $updatePassword = DB::table('password_resets')
            ->where([
                'email' => $request->email,
                'token' => $request->token
            ])
            ->first();

        if (!$updatePassword) {
            return back()->withInput()->with('error', 'Invalid token!');
        }
        $is_email_exist = User::where('email', $request->email)->exists();
        if ($is_email_exist) {
            $user = User::where('email', $request->email)
                ->update(['password' => Hash::make($request->password)]);

            DB::table('password_resets')->where(['email' => $request->email])->delete();

            return view('auth.redirectToLogin');
        } else {
            return response()->json([
                'message' => 'Email is not valid',
                'status' => 500,
            ]);
            // return redirect()->back()->withInput()->withErrors('error', $validator);
        }
    }
}
