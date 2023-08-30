<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Vendor;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use SbscPackage\Ecommerce\Interfaces\RoleInterface;
use SbscPackage\Ecommerce\Notifications\PendingVendorNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Notification;
use Carbon\Carbon;
use SbscPackage\Ecommerce\Models\EcommerceVendor;

class RegisterController extends BaseController
{
    /**
     * Vendor Sign up
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        /**
         * Validate Data
         */
        $validate = $this->validateRegister($request);
        /**
         * if validation fails
         */
        if ($validate->fails()) {
            return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all());
        }

        $data = $request->only('firstname', 'lastname', 'phoneno', 'email', 'password');
        $data["password"] =  Hash::make($request->password);

        try {
            DB::beginTransaction();

            $user = User::create($data);
            // if (isset($request->userRole)) {
            //     $userRole = $request->userRole;
            // } else {
                $userRole = DB::table('roles')->where('slug', 'ecommercevendor')->first();
            // }

            if (isset($userRole)){
                $user->attachRole($userRole->id);
                $allVendorPermission = config('roles.models.permission')::where('description', '=', 'Product Management')
                ->orWhere('description', '=', 'Order Management')->get();
                foreach ($allVendorPermission as $permission) {
                    $user->attachPermission($permission);
                }
            }

            //create vendor record
            $vendorRecord = EcommerceVendor::create([
                'user_id' => $user->id,
                'state' => $request->state,
                'city' => $request->city,
                'country' => $request->country,
                'country' => $request->country,
                'business_name' => $request->business_name,
            ]);
    
            $verification_code = Str::random(30); //Generate verification code
            $otpCode = random_int(10000, 99999); //generate random num
            DB::table('user_verifications')->insert(['user_id' => $user->id, 'otp' => $otpCode, 'token'=>$verification_code, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);

            $maildata = [
                'email' => $data['email'],
                'name' => $data["firstname"],
                'verification_code' => $verification_code,
                'subject' => "Please verify your email address.",
            ];

            $dataToLog = [
                'causer_id' => $user->id,
                'action_id' => $user->id,
                'action_type' => "Models\User",
                'log_name' => "User account created successfully",
                'description' => "{$user->firstname} {$user->lastname} account created successfully",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            Notification::route('mail', $request->email)->notify(new PendingVendorNotification($maildata));
            DB::commit();
            return JsonResponser::send(false, "Thanks for signing up! Please check your email to complete your registration.", [], 201);
        } catch (\Throwable $error) {
            DB::rollback();
            return $error->getMessage();
            logger($error);
            return JsonResponser::send(true, "Internal server error", null, 500);
        }
    }

    /**
     * Resend Email Token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendCode(Request $request)
    {
        /**
         * Validate Data
         */
        $validate = $this->validateResendCode($request);
        /**
         * if validation fails
         */
        if ($validate->fails()) {
            return JsonResponser::send(true, "Validation Failed", $validate->errors()->all());
        }

        $email = $request->email;
        $user = User::where("email", $email)->first();
        if (!$user) {
            return JsonResponser::send(true, "User not found", null, 404);
        }

        if ($user->is_verified) {
            return JsonResponser::send(true, "Account already verified", null, 400);
        }

        $verification_code = Str::random(30); //Generate verification code
        $otpCode = random_int(10000, 99999); //generate random num
        DB::table('user_verifications')->insert(['user_id' => $user->id, 'otp' => $otpCode, 'token'=>$verification_code, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);

        $maildata = [
            'email' => $email,
            'name' => $user->firstname,
            'verification_code' => $verification_code,
            'subject' => "Please verify your email address.",
        ];

        // Mail::to($email)->send(new VerifyEmail($maildata));
        return JsonResponser::send(false, "Verification link sent successfully.", null);
    }

    /**
     * Validate register request
     */
    protected function validateRegister($request)
    {
        $rules =  [
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'phoneno' => 'required|max:12|unique:users',
            'address' => 'required',
            'state' => 'required',
            'city' => 'required',
            'business_name' => 'required',
            'password' => [
                'required',
                'string',
                'min:8',             // must be at least 8 characters in length
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain a special character
            ],
            'confirmPassword' => 'same:password'
        ];

        $validatedData = Validator::make($request->all(), $rules);
        return $validatedData;
    }

    /**
     * Validate resend code request
     */
    protected function validateResendCode($request)
    {
        $rules =  [
            'email' => 'required|email|max:255',
        ];

        $validatedData = Validator::make($request->all(), $rules);
        return $validatedData;
    }
}
