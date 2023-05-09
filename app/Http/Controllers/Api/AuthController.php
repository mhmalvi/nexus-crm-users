<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * User List
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse Details array
     */
    public function userList(Request $request)
    {

        if (!isset($request->users)) {
            return response()->json([
                'status' => false,
                'message' => 'User id required'
            ], 406);
        }
        $userIdArray = json_decode($request->users);
        //dd($userIdArray);

        try {

            $data = User::leftJoin('user_profile', function ($join) {
                $join->on('user_profile.user_id', '=', 'users.id');
            })->whereIn('users.id', $userIdArray)
                ->where('users.status', 1)
                //->where('lead_details.client_id', '=', $request->client_id)
                ->get();

            if ($data == "" || count($data) == 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ], 401);
            }
            //dd($data->toArray());

            return response()->json([
                'status' => true,
                'message' => 'All User List',
                'data' => $data->toArray()
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Create User
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse  User
     */
    public function createUser(Request $request)
    {
        DB::beginTransaction();

        try {
            //Validated
            $validateUser = Validator::make(
                $request->all(),
                [
                    'email' => 'required|email|unique:users,email',
                    'role_id' => 'required'
                ]
            );
            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }
            $randomPassword = $this->_randomPassword();
            // Business type used in Company service this time
            $userId = DB::table('users')->insertGetId([
                'email' => $request->email,
                'password' => Hash::make($randomPassword),
                'role_id' => $request->role_id,
                'contact_number' => isset($request->contact_number) ? $request->contact_number : '',
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
                'date_of_birth' => isset($request->date_of_birth) ? $request->date_of_birth : ''
            ]);
            DB::commit();

            $userServiceAPI = env('EMAIL_SERVICE_API');

            $response = Http::post('https://crm-mailer.onrender.com/api/registration', [
                'username' => $request->email,
                'email' => $request->email,
                'password' => $randomPassword
            ]);

            $userData = [
                'user_name' => $request->full_name,
                'user_id' => $userId,
                'password' => $randomPassword
            ];
            return response()->json([
                'status' => true,
                'message' => 'User Created Successfully',
                'data'  => $userData
            ], 201);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * User Profile Details
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserDetails(Request $request)
    {
        if (!isset($request->user_id))
            return response()->json([
                'status' => false,
                'message' => 'validation error',
            ], 401);
        try {
            $user = UserProfile::where('user_id', '=', $request->user_id)->first();
            if ($user == "") {
                return response()->json([
                    'status' => false,
                    'message' => 'User Data not found',
                ], 401);
            }

            return response()->json([
                'status' => true,
                'message' => 'User Profile Data',
                'data'    => $user->toArray()
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    /**
     * Update User
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUser(Request $request)
    {
        if (!isset($request->user_id))
            return response()->json([
                'status' => false,
                'message' => 'validation error',
            ], 401);

        try {
            $user = UserProfile::where('user_id', '=', $request->user_id)->first();
            if ($user == "") {
                return response()->json([
                    'status' => false,
                    'message' => 'User Data not found',
                ], 401);
            }

            if (isset($request->full_name))
                $user->full_name = $request->full_name;
            if (isset($request->address))
                $user->address = $request->address;
            if (isset($request->qualification))
                $user->qualification = $request->qualification;
            if (isset($request->region))
                $user->region = $request->region;
            if (isset($request->postcode))
                $user->postcode = $request->postcode;
            if (isset($request->work_experiences))
                $user->work_experiences = $request->work_experiences;
            if (isset($request->location))
                $user->location = $request->location;
            if (isset($request->profession))
                $user->profession = $request->profession;
            if (isset($request->secondary_contact))
                $user->secondary_contact = $request->secondary_contact;
            if (isset($request->date_of_birth))
                $user->date_of_birth = $request->date_of_birth;
            $user->save();
            return response()->json([
                'status' => true,
                'message' => 'User Profile Update Successfully',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Update User Status
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserStatus(Request $request)
    {
        if (!isset($request->id) || !isset($request->status))
            return response()->json([
                'status' => false,
                'message' => 'User Id and Status is required',
            ], 401);

        try {
            $user = User::find($request->id);
            if ($user == "") {
                return response()->json([
                    'status' => false,
                    'message' => 'User Data not found',
                ], 401);
            }

            $user->status = ($request->status == 1) ? 1 : 0;
            $user->save();
            return response()->json([
                'status' => true,
                'message' => 'User Status Update Successfully',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Update User suspend status
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function usersSuspend(Request $request)
    {
        if (!isset($request->users) || !isset($request->suspend)) {
            return response()->json([
                'status' => false,
                'message' => 'User id required'
            ], 406);
        }
        $userIdArray = json_decode($request->users);
        //dd($userIdArray);

        $suspend = $request->suspend;
        //$status = ($request->suspend==1)?0:1;
        $statusArray = [
            'suspend' => $suspend
            // 'status' => $status
        ];
        //dd($statusArray);

        //        return response()->json([
        //            'status' => false,
        //            'message' => 'User Data not found',
        //            'data' =>$request->suspend
        //        ], 401);

        try {

            User::find(collect($userIdArray)->pluck('id')->toArray())->map(function ($item, $key) use ($statusArray) {
                $item['suspend'] = $statusArray['suspend'];
                return $item->save();
            });
            return response()->json([
                'status' => true,
                'message' => 'User Status Update Successfully',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Update User suspend status
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function usersSuspendById(Request $request)
    {
        if (!isset($request->user_id) || !isset($request->suspend)) {
            return response()->json([
                'status' => false,
                'message' => 'User id required'
            ], 406);
        }


        try {

            $user = User::find($request->user_id);
            if ($user == "") {
                return response()->json([
                    'status' => false,
                    'message' => 'User Data not found',
                ], 401);
            }

            if (isset($request->suspend))
                $user->suspend = $request->suspend;

            $user->save();
            return response()->json([
                'status' => true,
                'message' => 'User Profile Update Successfully',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Update User Password
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        if (!isset($request->user_id))
            return response()->json([
                'status' => false,
                'message' => 'validation error',
            ], 401);

        try {
            $user = User::find($request->user_id);
            if ($user == "") {
                return response()->json([
                    'status' => false,
                    'message' => 'User Data not found',
                ], 401);
            }
            if (isset($request->password))
                $user->password = Hash::make($request->password);
            $user->flag = 2;
            $user->save();
            return response()->json([
                'status' => true,
                'message' => 'Password Reset Successfully',
            ], 205);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Update Forgot Password
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse ID
     */
    public function forgotPassword(Request $request)
    {
        if (!isset($request->email))
            return response()->json([
                'status' => false,
                'message' => 'validation error',
            ], 401);

        try {
            $user = User::where('email', '=', $request->email)->where('suspend', 0)->first();
            if ($user == "") {
                return response()->json([
                    'status' => false,
                    'message' => 'User Data not found',
                ], 401);
            }
            $user->verification_code = $user->id . $this->_randomPassword() . '-' . $user->id;
            $user->flag = 2;
            $user->save();

            $userServiceAPI = env('EMAIL_SERVICE_API', '');
            //dd($userServiceAPI);
            $userData = [
                'email' => $user->email,
                'verification_code' => $user->verification_code
            ];

            $response = Http::post($userServiceAPI . '/forget-password', [
                'data' => json_encode($userData)
            ]);
            //dd($response->status());

            if ($response->status() != '201') {
                return response()->json([
                    'status' => false,
                    'message' => 'Email Not Sent',
                ], 401);
            }

            return response()->json([
                'status' => true,
                'message' => 'Email sent with link for forgotten password'
            ], 202);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Update Verification Code for Forgot Password
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateVerificationCode(Request $request)
    {
        if (!isset($request->verification_code))
            return response()->json([
                'status' => false,
                'message' => 'validation error',
            ], 401);

        $verificationCodeArray = explode('-', $request->verification_code);
        if (!isset($verificationCodeArray[1])) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid verification Code',
            ], 401);
        }
        //dd($verificationCodeArray);
        try {

            $user = User::where('id', $verificationCodeArray[1])->where('verification_code', $request->verification_code)->where('suspend', 0)->first();

            if ($user == "") {
                return response()->json([
                    'status' => false,
                    'message' => 'User verification Code not found',
                ], 401);
            }

            $user->verification_code = '';
            $user->save();
            return response()->json([
                'status' => true,
                'user_id' => $verificationCodeArray[1],
                'message' => 'Verification Code Update Successfully',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Random Password generate
     * @param Request void
     * @return 8 character password
     */
    private function _randomPassword()
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890' . time();
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

    /**
     * Login The User
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginUser(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'email' => 'required|email',
                    'password' => 'required'
                ]
            );

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            if (!Auth::attempt($request->only(['email', 'password']))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email & Password does not match with our record.',
                ], 401);
            }

            if (Auth::user()->suspend != 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your Account is Currently Suspend, Please Contact with Support Team',
                ], 401);
            }

            if (Auth::user()->status != 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your Account is Currently Inactive, Please Contact with Support Team',
                ], 401);
            }
            //$user = User::where('email', $request->email)->first();
            // dd($user->id);

            //dd(Auth::user()->id);

            $data = User::join('user_profile', function ($join) {
                $join->on('user_profile.user_id', '=', 'users.id');
            })->where('users.id', Auth::user()->id)
                //->where('lead_details.client_id', '=', $request->client_id)
                ->first();

            if ($data == "") {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ], 401);
            }
            $clientId = 0;
            $ac_k = '';
            $roleArray = [1, 2, 3, 4, 5];
            if (isset($data->role_id) && in_array($data->role_id, $roleArray)) {
                // $clientId =  $data->user_id;
                $companyServiceAPI = env('COMPANY_SERVICE_API', '');
                //dd($companyServiceAPI);

                $response = Http::post($companyServiceAPI . '/company/details/user', [
                    'user_id' => $data->user_id,
                    'role_id' => $data->role_id
                ]);
                $jsonArray = json_decode($response->body());
                if ($jsonArray != "" && isset($jsonArray->data->company_id)) {
                    $clientId = $jsonArray->data->company_id;
                    $ac_k = $jsonArray->data->fb_ac_credential;
                }
            }
            $data->client_id = $clientId;
            $data->ac_k = $ac_k;
            //dd($data);
            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'token' => Auth::user()->createToken("API TOKEN")->plainTextToken,
                'data'  => $data
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
