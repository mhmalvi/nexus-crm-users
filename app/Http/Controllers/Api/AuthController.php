<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Mail\SignupMail;
use App\Models\ActiveToken;
use App\Models\UserProfile;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\RegistrationMail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * User List
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse Details array
     */
    public function token_exists(Request $request)
    {
        //  dd(Auth::user()->email);
        $token_exist = ActiveToken::where('token', $request->bearerToken())->exists();
        // dd($data);
        if ($token_exist) {
            $token = ActiveToken::where('token', $request->bearerToken())->first();
            return response()->json([
                'data' => 1,
                'role' => $token->role_id
            ]);
        } else {
            return response()->json([
                'message' => 'unauthenticated'
            ]);
        }
    }

    public function get_user_details(Request $request)
    {
        //  dd($request->all());
        $user = User::leftJoin('user_profile', function ($join) {
            $join->on('user_profile.user_id', '=', 'users.id');
        })->where('users.id', $request->user_id)->where('users.role_id', $request->role)->where('users.status', 1)->where('users.suspend', 0)->first();
        if ($user) {
            return response()->json([
                'message' => 'success',
                'status' => 200,
                'data' => $user
            ]);
        }
    }
    public function userList(Request $request)
    {

        if (!isset($request->users)) {
            return response()->json([
                'status' => false,
                'message' => 'User id required'
            ], 406);
        }
        $userIdArray = json_decode($request->users);
        try {
            if ($request->role == 5) {
                $data = User::leftJoin('user_profile', function ($join) {
                    $join->on('user_profile.user_id', '=', 'users.id');
                })->whereIn('users.id', $userIdArray)
                    ->where('users.status', 1)->where('role_id', 5)->where('users.suspend', '=', 0)
                    //->where('lead_details.client_id', '=', $request->client_id)
                    ->get();
            } else {
                $data = User::leftJoin('user_profile', function ($join) {
                    $join->on('user_profile.user_id', '=', 'users.id');
                })->whereIn('users.id', $userIdArray)
                    ->where('users.status', 1)
                    //->where('lead_details.client_id', '=', $request->client_id)
                    ->get();
            }


            // if($data =="" || count($data)==0){
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'User not found',
            //     ], 401);
            // }

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

    public function fetch_sales_user_in_lead_details(Request $request, $company_id)
    {
        // $flag = Http::withToken($request->bearerToken())->post(env('APP_URL', '') . '/api/check-if-token-exists');
        // $flag_receive = $flag['data'];
        // if ($flag_receive == 1) {
        $data =
            DB::table('users')
            ->join('user_profile', 'users.id', '=', 'user_profile.user_id')
            ->select('users.*', 'user_profile.*')
            ->where('users.role_id', '=', 5)
            ->where('suspend', 0)
            ->get();
        // dd(json_encode($data));
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'not found',
                'status' => 404
            ], 404);
        }
        // }
    }

    public function fetch_user(Request $request, $role, $status)
    {    ///fetch student admins ////
        // $flag = Http::withToken($request->bearerToken())->post(env('APP_URL', '') . '/api/check-if-token-exists');
        // $flag_receive = $flag['data'];
        // if ($flag_receive == 1) {
        $user = User::where('role_id', $role)->where('suspend', $status)->get();
        if (count($user) > 0) {
            return response()->json([
                'message' => 'success',
                'status' => 200,
                'data' => $user
            ], 200);
        } else {
            return response()->json([
                'message' => 'Failed',
                'status' => 200,
                'data' => []
            ], 200);
        }
        // }
    }

    public function register(RegisterRequest $request)
    {
        try {
            $token = Str::random(64);
            $isTokenExists = User::where('token', $token)->exists();
            if ($isTokenExists) {
                $token = Str::random(64);
            }
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => 3,
                'status' => 1,
                'suspend' => 0,
                'token' => $token,
                'verification_status' => 0
            ]);
            $user_profile = UserProfile::create([
                'user_id' => $user->id
            ]);
            Mail::to($request->email)->queue(new SignupMail($token, $request->email));
            if ($user_profile) {
                return response()->json([
                    'message' => 'Registration successful',
                    'status' => 201
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Registration failed',
                    'status' => 500
                ], 500);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function email_verification($token)
    {
        $result = User::where('token', $token)->first();
        if ($result) {
            $result->verification_status = 1;
            $response = $result->save();
            if ($response) {
                return view('registration_mail.email_verification_success');
            }
        } else {
            abort(404);
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
            // if($request->role_id !== 5){
            //     $validateUser = Validator::make($request->all(),
            //         [
            //             'email' => 'required|email|unique:users,email',
            //             'role_id' => 'required'
            //         ]);
            //     if($validateUser->fails()){
            //         return response()->json([
            //             'status' => false,
            //             'message' => 'validation error',
            //             'errors' => $validateUser->errors()
            //         ], 401);
            //     }
            // }
            // $randomPassword = $this->_randomPassword();
            // Business type used in Company service this time
            // $userId = DB::table('users')->insertGetId([
            //     'email' => $request->email,
            //     // 'password' => Hash::make($randomPassword),
            //     'role_id' => $request->role_id,
            //     'contact_number' => isset($request->contact_number) ? $request->contact_number : '',
            //     // 'abn_number' => isset($request->abn_number)?$request->abn_number:'',
            //     'flag' => 1,
            //     'status' => 1,
            //     'suspend' => 0,
            //     'created_at' => Carbon::parse(now())->toDateTime(),
            //     'updated_at' => Carbon::parse(now())->toDateTime()
            // ]);
            $user = DB::table('users')->where('email', $request->email)->first();
            $user->contact_number = $request->contact;
            $user->full_name = $request->username;
            $user->save();
            $profile = DB::table('user_profile')->where('user_id', $user->id)->first();
            $profile->full_name = $request->username;
            $profile->website = $request->website;
            $profile->address = $request->company_address;
            $profile->save();
            DB::connection('company')->table('companies')->insert([
                'name' => $request->company_name,
                'contact' => $request->contact,
                'business_email' => $request->email,
                'address' => $request->company_address,
                'abn' => $request->abn ? $request->abn : '',
                'website' => $request->website,
                'trading_name' => $request->trading_name ? $request->trading_name : '',
                'rto_code' => $request->rto_code,
                'country_name' => $request->country_name ? $request->country_name : '',
                'admin' => $user->id,
                'active' => 1,
            ]);
            // DB::table('user_profile')->insert([
            //     'user_id' => $userId,
            //     'full_name' => isset($request->full_name) ? $request->full_name : '',
            //     'address' => isset($request->address) ? $request->address : '',
            //     'qualification' => isset($request->qualification) ? $request->qualification : '',
            //     'region' => isset($request->region) ? $request->region : '',
            //     'postcode' => isset($request->postcode) ? $request->postcode : '',
            //     'work_experiences' => isset($request->work_experiences) ? $request->work_experiences : '',
            //     'location' => isset($request->location) ? $request->location : '',
            //     'profession' => isset($request->profession) ? $request->profession : '',
            //     'secondary_contact' => isset($request->secondary_contact) ? $request->secondary_contact : '',
            //     'date_of_birth' => isset($request->date_of_birth) ? $request->date_of_birth : '',
            //     'website' => isset($request->website) ? $request->website : ''
            // ]);
            DB::commit();

            Mail::to($request->email)->queue(new RegistrationMail($request->email, $request->full_name));

            $userData = [
                'user_name' => $request->full_name,
                'user_email' => $request->email
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
                'data' => $user->toArray()
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function get_sales_user()
    {
        $data =
            DB::table('users')
            ->join('user_profile', 'users.id', '=', 'user_profile.user_id')
            ->select('users.*', 'user_profile.*')
            ->where('users.role_id', '=', 5)
            ->where('suspend', 0)
            ->get();
        // dd(json_encode($data));
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'not found',
                'status' => 404
            ], 404);
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
        // dd("fdfdgf");
        if (!isset($request->id) || !isset($request->status))
            return response()->json([
                'status' => false,
                'message' => 'User Id and Status is required',
            ], 401);

        try {
            $user = DB::table('users')->where('id', $request->id)
                ->update(
                    [
                        'status' => 0,
                        // 'deleted_by' => $user_id
                    ]
                );
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
        // dd($request->suspend);
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
        // dd("hello");
        if (!isset($request->user_id) || !isset($request->suspend)) {
            return response()->json([
                'status' => false,
                'message' => 'User id required'
            ], 406);
        }


        try {
            // dd("gfdgg");

            $user = User::find($request->user_id);
            // dd($user);
            if ($user == "") {
                return response()->json([
                    'status' => false,
                    'message' => 'User Data not found',
                ], 401);
            }
            // dd($request->suspend);

            if (isset($request->suspend))
                $user->suspend = $request->suspend;
            // Http::post('https://crmcompany.quadque.digital/api/suspend-sales-in-company-sales-employee-table',['user_id'=>$request->user_id,'status'=>$request->suspend]);
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
            $user = User::where('email', $request->email)->first();
            // dd($user->id);

            //dd(Auth::user()->id);
            // $activeSessionsCount = Auth::user()->sessions()->count();

            // dd($activeSessionsCount);
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
            $companyServiceAPI = env('COMPANY_SERVICE_API', '');
            if (isset($data->role_id) && in_array($data->role_id, $roleArray)) {
                // $clientId =  $data->user_id;

                // dd($companyServiceAPI);

                $response = Http::post('https://crmcompany.queleadscrm.com/api/company/details/user', [
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
            $token = Auth::user()->createToken("API TOKEN")->plainTextToken;
            // $user = User::where('email', $request->email)->first();
            // // dd($user);
            // $user->token = $token;
            // $user->save();
            //dd($data);
            ActiveToken::create([
                'email' => $request->email,
                'token' => $token,
                'ip' => $request->ip(),
                'role_id' => $user->role_id,
                'user_id' => $user->id
            ]);
            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'token' => $token,
                'data' => $data
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function delete_company_id(Request $request)
    {
        $user = User::find($request->company_id);
        $user->delete();
        $user_profile = UserProfile::where('user_id', $request->company_id)->first();
        $user_profile->delete();
    }

    public function destroy_user(Request $request)
    {
        // $user = DB::table('users')->where('id',$request->user_id)->leftJoin('user_profile',function($join){
        //     $join->on('users.id','=','user_profile.user_id');

        // })->delete();
        // dd($request->user_id);
        $user_profile = DB::table('user_profile')->where('user_id', $request->user_id)->delete();
        $user = DB::table('users')->where('id', $request->user_id)->delete();
        // dd(json_encode($user));
        //  dd($user);
    }

    public function logout(Request $request)
    {
        // dd(json_decode(auth('sanctum')->user()->currentAccessToken())->tokenable->token);
        if ($request->bearerToken() !== null) {
            $response = auth('sanctum')->user()->currentAccessToken()->delete();
            $token_exist = ActiveToken::where('token', $request->bearerToken())->where('ip', $request->ip())->exists();
            // dd($token);
            if ($token_exist) {
                $token = ActiveToken::where('token', $request->bearerToken())->where('ip', $request->ip())->first();
                $result = $token->delete();
            }

            if ($response && $result) {
                return response()->json('Logout successful');
            } else {
                return response()->json('Unauthorized attempt');
            }
        } else {
            return response()->json(
                'Invalid token'
            );
        }
    }
}
