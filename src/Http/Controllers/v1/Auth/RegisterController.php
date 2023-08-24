<?php

namespace SbscPackages\Authentication\Http\Controllers\v1\Auth;

use SbscPackages\Authentication\Http\Controllers\Controller;
use Illuminate\Http\Request;
use SbscPackages\Authentication\Http\Requests\CreateUserRequest;
use SbscPackages\Authentication\Models\User;
use SbscPackages\Authentication\Responser\JsonResponser;
use SbscPackages\Authentication\Helpers\ProcessAuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use SbscPackages\Authentication\Notifications\PendingUserNotification;
use Illuminate\Support\Str;
use Notification;
use Carbon\Carbon;


class RegisterController extends Controller
{
    
    public function store(CreateUserRequest $request)
    {

        try {
            DB::beginTransaction();

            $recordExit = DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            $user = User::create([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'phoneno' => $request->phoneno,
                'email' => $request->email,
            ]);

            // $token = $user->createToken('API_TOKEN')->plainTextToken;

            $verification_code = Str::random(30); //Generate verification code
            $otpCode = random_int(10000, 99999); //generate random num
            DB::table('user_verifications')->insert(['user_id' => $user->id, 'otp' => $otpCode, 'token'=>$verification_code, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);

            DB::table('password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => $verification_code,
                'created_at' => Carbon::now()
            ]);

            $data = [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'phoneno' => $user->phoneno,
                'email' => $user->email,
                'token' => $verification_code,
            ];

            Notification::route('mail', $request->email)->notify(new PendingUserNotification($data));

            $sanctumToken = $user->createToken('tokens')->plainTextToken;

            $dataToLog = [
                'causer_id' => $user->id,
                'action_id' => $user->id,
                'action_type' => "Models\User",
                'log_name' => "Account created successfully",
                'description' => "{$user->firstname} {$user->lastname} account created successfully",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            DB::commit();
            return JsonResponser::send(false, "Account created successfully", $user, 200);

        } catch (\Throwable $error) {
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }
}
