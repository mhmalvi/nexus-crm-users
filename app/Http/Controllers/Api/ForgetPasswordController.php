<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\ForgetPasswordMail;
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

        // Mail::send('email.forgetpassword', ['token' => $token], function ($message) use ($request) {
        //     $message->to($request->email);
        //     $message->subject('Reset Password');
        // });
        Mail::to($request->email)->queue(new ForgetPasswordMail($request->email, $token));
        
        return response()->json([
            'message' => 'success',
            'status' => 200
        ], 200);
    }

    public function showResetPasswordForm($token)
    {
        $isTokenExists = DB::table('password_resets')->where('token', $token)->exists();
        if (!$isTokenExists) {
            return response()->json([
                'Url does not exists'
            ]);
        }
        return view('auth.forgetPasswordLink', ['token' => $token]);
    }

    public function submitResetPasswordForm(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => [
                'required',
                'string',
                'min:8', // must be at least 10 characters in length
                'regex:/[a-z]/', // must contain at least one lowercase letter
                'regex:/[A-Z]/', // must contain at least one uppercase letter
                'regex:/[0-9]/', // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain a special character
            ],
            'password_confirmation' => 'required'
        ]);
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
        }
    }
}
