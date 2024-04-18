<?php

namespace App\Http\Controllers\Api;

use DateTime;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Company;
use App\Mail\SignupMail;
use App\services\Register;
use App\Models\ActiveToken;
use App\Models\UserProfile;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\CRMFilesystem;
use App\Mail\RegistrationMail;
use Illuminate\Support\Facades\DB;
use App\Interfaces\CreateInterface;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\RegisterRequest;
use App\Services\CreateSubscriptionService;
use Illuminate\Support\Facades\Validator;
use App\Services\CreateTrialPackageService;
use App\Services\UpdateCustomerService;

class AuthController extends Controller
{
    private $createTrialPackageService;
    private $createSubscription;
    private $updateStripeCustomer;
    public function __construct(CreateTrialPackageService $createTrialPackageService, CreateSubscriptionService
    $createSubscription, UpdateCustomerService $updateStripeCustomer)
    {
        $this->createTrialPackageService = $createTrialPackageService;
        $this->createSubscription = $createSubscription;
        $this->updateStripeCustomer = $updateStripeCustomer;
    }
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

    public function register(RegisterRequest $request, CreateInterface $createStripeCustomer)
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
            $data = [
                $company_name = "Customer",
                $email = $request->email,
            ];
            $result = $createStripeCustomer->create($data);
            if ($request->package == 'trial') {
                Company::create([
                    'connect_id' => $result->id,
                    'business_email' => $request->email,
                    'package' => $request->package,
                    'interval' => $request->interval,
                    'admin' => $user->id
                ]);
            } else {
                Company::create([
                    'connect_id' => $result->id,
                    'business_email' => $request->email,
                    'package' => $request->package,
                    'interval' => $request->interval,
                    'price_id' => $request->priceId,
                    'admin' => $user->id
                ]);
            }

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
        // DB::beginTransaction();
        try {
            $user = User::where('email', $request->email)->first();
            $user->contact_number = $request->contact;
            $user->verification_status = 2;
            $user->save();
            ///////////////////////////////////////////////////////////////////////////////////
            $profile = UserProfile::where('user_id', $user->id)->first();
            $profile->full_name = $request->username;
            $profile->website = $request->website;
            $profile->address = $request->company_address;
            $profile->save();
            ///////////////////////////////////////////////////////////////////////////////////

            $file = new CRMFilesystem();
            $file->user_id = $user->id;
            $file->document_name = "company_image/buildings.svg";
            $file->document_details = "company image";
            $file->save();
            ///////////////////////////////////////////////////////////////////////////////////
            $company = Company::where('business_email', $request->email)->first();
            // dd($company->connect_id);
            $company->name = $request->company_name;
            $company->contact = $request->contact;
            $company->address = $request->company_address;
            $company->abn = $request->abn ? $request->abn : '';
            $company->website = $request->website ? $request->website : "";
            $company->trading_name = $request->trading_name ? $request->trading_name : '';
            $company->rto_code = $request->company_code;
            $company->country_name = $request->country_name ? $request->country_name : '';
            $company->admin = $user->id;
            $company->active = 1;
            $company->industry = $request->industry;
            $company->logo_id = $file->id;
            $company->save();
            ///////////////////////////////////////////////////////////////////////////////////
            $file->client_id = $company->id;
            $file->save();
            $data = [
                $company_name = $request->company_name,
                $cus_id = $company->connect_id,
            ];



            // $ip = $request->ip();
            // // dd($ip);
            // $url = 'http://ip-api.com/json/' . $ip;
            // $tz = file_get_contents($url);
            // $tz = json_decode($tz, true)['timezone'];
            // // dd($tz);
            // $zone = json_encode(Carbon::now($tz));
            // // dd($zone);
            // $time = substr($zone, 12, 13);
            // // dd($time);
            // $time_str = substr($time, 0, 8);
            // // dd($time_sub);
            // $date_str = substr($zone, 1, 10);
            // // dd($sub_str);
            // $date_time_str = $date_str . ' ' . $time_str;

            // dd($data);
            $result = $this->updateStripeCustomer->updateCustomer($data);
            // dd($result);
            if ($request->package == 'Trial') {
                $company->package = $request->package;
                $current_date = Carbon::now();
                $end_date = $current_date->addDays(30);
                $company->end_date = Carbon::parse($end_date)->format("Y-m-d H:i:s");
                // $company->interval = 'month';
            } else {
                // dd($result);
                $company->package = $request->package;
                $subscription = $this->createSubscription->create_subscription($result, $company->price_id);
                $company->subscription_id = $subscription->id;
                $company->end_date = $subscription->current_period_end;
                // $company->interval = $request->interval;
            }
            $company->save();
            $user_data = DB::table('users')->join('user_profile', 'users.id', '=',
            'user_profile.user_id')->join('company.companies','users.id', '=', 'companies.admin')->where('users.id',
            $user->id)->first();
            Mail::to($request->email)->queue(new RegistrationMail($request->email, $request->full_name));
            // DB::commit();
            return response()->json([
                'status' => 201,
                'message' => 'Company Created Successfully',
                'data' => $user_data,
                'company' => $company
            ], 201);
        } catch (\Throwable $th) {
            // DB::rollback();
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

    //  public function createPackage(){
    //     $response = $this->createTrialPackageService->createPackage();
    //     // dd($response);
    //  }
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
            if (
                $user->verification_status == 1 || $user->verification_status
                == 2
            ) {
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
                // $companyServiceAPI = env('COMPANY_SERVICE_API', '');
                if (isset($data->role_id) && in_array($data->role_id, $roleArray)) {
                    // $clientId = $data->user_id;

                    // dd($companyServiceAPI);

                    $response = Http::crm_company()->post('/company/details/user', [
                        'user_id' => $data->user_id,
                        'role_id' => $data->role_id
                    ]);
                    $jsonArray = json_decode($response->body());
                    if ($jsonArray != "" && isset($jsonArray->data->company_id)) {
                        $clientId = $jsonArray->data->company_id;
                        $ac_k = $jsonArray->data->fb_ac_credential;
                        if (isset($jsonArray->data->connect_id)) {
                            $customer_id = $jsonArray->data->connect_id;
                        }
                        if (isset($jsonArray->data->end_date)) {
                            $end_date = $jsonArray->data->end_date;
                        }
                        if (isset($jsonArray->data->package)) {
                            $package = $jsonArray->data->package;
                        }
                        if (isset($jsonArray->data->subscription_id)) {
                            $subscription_id = $jsonArray->data->subscription_id;
                        }
                        if (isset($jsonArray->data->active)) {
                            $active = $jsonArray->data->active;
                        }
                        if (isset($jsonArray->data->interval)) {
                            $interval = $jsonArray->data->interval;
                        }
                    }
                }
                $data->client_id = $clientId;
                $data->ac_k = $ac_k;
                if (isset($customer_id)) {
                    $data->customer_id = $customer_id;
                }
                if (isset($end_date)) {
                    // $data->end_date = gmdate('d.m.Y H:i', strtotime($end_date));
                    $data->end_date = $end_date;
                    // $data->end_date = $end_dateTime->format($end_dateTime);
                }
                if (isset($package)) {
                    $data->package = $package;
                }
                if (isset($subscription_id)) {
                    $data->subscription_id = $subscription_id;
                }
                if (isset($active)) {
                    $data->active = $active;
                }
                if (isset($interval)) {
                    $data->interval = $interval;
                }
                $token = Auth::user()->createToken("API TOKEN")->plainTextToken;
                // $user = User::where('email', $request->email)->first();
                // // dd($user);
                // $user->token = $token;
                // $user->save();
                //dd($data);
                DB::connection('token')->table('token')->insert([
                    'email' => $request->email,
                    'token' => 'Bearer ' . $token,
                    'ip' => $request->ip(),
                    'role_id' => $user->role_id,
                    'user_id' => $user->id
                ]);
                // ActiveToken::create([
                // 'email' => $request->email,
                // 'token' => $token,
                // 'ip' => $request->ip(),
                // 'role_id' => $user->role_id,
                // 'user_id' => $user->id
                // ]);
                return response()->json([
                    'status' => true,
                    'message' => 'User Logged In Successfully',
                    'token' => $token,
                    'data' => $data
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Account not verified',
                    'token' => ""
                ]);
            }
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
        // if ($request->bearerToken() !== null) {
        $token_exist = DB::connection('token')->table('token')->where('token', 'Bearer ' . $request->token)->where('email', $request->email)->where(
            'user_id',
            $request->user_id
        )->exists();
        // dd($token);
        if ($token_exist) {
            $token = DB::connection('token')->table('token')->where('token', 'Bearer ' . $request->token)->where(
                'email',
                $request->email
            )->where(
                'user_id',
                $request->user_id
            )->delete();
            // dd($token);
            // $result = $token->delete();
            if ($token) {
                return response()->json('Logout successful');
            } else {
                return response()->json('Unauthorized attempt');
            }
        }


        // } else {
        //     return response()->json(
        //         'Invalid token'
        //     );
        // }
    }
}
