<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Mail\RegistrationMail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function addUser(Request $request)
    {
        DB::beginTransaction();

        try {
            //Validated
            // if($request->role_id !== 5){
            // $validateUser = Validator::make($request->all(),
            // [
            // 'email' => 'required|email|unique:users,email',
            // 'role_id' => 'required'
            // ]);
            // if($validateUser->fails()){
            // return response()->json([
            // 'status' => false,
            // 'message' => 'validation error',
            // 'errors' => $validateUser->errors()
            // ], 401);
            // }
            // }
            $randomPassword = $this->_randomPassword();
            // Business type used in Company service this time
            $userId = DB::table('users')->insertGetId([
                'email' => $request->email,
                'password' => Hash::make($randomPassword),
                'role_id' => $request->role_id,
                'contact_number' => isset($request->contact_number) ? $request->contact_number : '',
                // 'abn_number' => isset($request->abn_number)?$request->abn_number:'',
                'flag' => 1,
                'status' => 1,
                'suspend' => 0,
                'created_at' => Carbon::parse(now())->toDateTime(),
                'updated_at' => Carbon::parse(now())->toDateTime()
            ]);
            DB::table('user_profile')->insert([
                'user_id' => $userId,
                'full_name' => isset($request->full_name) ? $request->full_name : '',
                'address' => isset($request->address) ? $request->address : '',
                'qualification' => isset($request->qualification) ? $request->qualification : '',
                'region' => isset($request->region) ? $request->region : '',
                'postcode' => isset($request->postcode) ? $request->postcode : '',
                'work_experiences' => isset($request->work_experiences) ? $request->work_experiences : '',
                'location' => isset($request->location) ? $request->location : '',
                'profession' => isset($request->profession) ? $request->profession : '',
                'secondary_contact' => isset($request->secondary_contact) ? $request->secondary_contact : '',
                'date_of_birth' => isset($request->date_of_birth) ? $request->date_of_birth : '',
                'website' => isset($request->website) ? $request->website : ''
            ]);
            DB::commit();

            Mail::to($request->email)->queue(new RegistrationMail($request->email, $request->full_name, $randomPassword));
            // $userServiceAPI = env('EMAIL_SERVICE_API', '');

            // $response = Http::post('https://crm-mailer.onrender.com/api/send-registration-mail', [
            // 'username'=>$request->full_name,
            // 'email'=>$request->email,
            // 'password' => $randomPassword
            // ]);

            $userData = [
                'user_name' => $request->full_name,
                'user_email' => $request->email,
                'user_id' => $userId,
                'password' => $randomPassword
            ];
            return response()->json([
                'status' => true,
                'message' => 'User Created Successfully',
                'data' => $userData
            ], 201);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
