<?php

namespace App\Http\Controllers\v1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Responser\JsonResponser;
use App\Helpers\ProcessAuditLog;
use Illuminate\Support\Facades\Notification;
use App\Notifications\LoginNotification;
use JWTAuth, DB;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'refresh', 'logout']]);
    }

    public function login(LoginRequest $request)
    {
       
       try {
            $credentials = request(['email', 'password']);

            if (!$token = auth()->attempt($credentials)) {
                return JsonResponser::send(true, 'Incorrect email or password', null);
            }

            // This will check if email have been verified
            if (!auth()->user()->is_verified) {
                return JsonResponser::send(true, 'Account not verified. Kindly verify your email', null);
            }

            // This will check if user has been deactivated
            if (!auth()->user()->is_active) {
                return JsonResponser::send(true, 'Your account has been deactivated. Please contact the administrator', null);
            }
            $user = User::find(auth()->user()->id);

            // Data to return
            $data = [
                "user" => $user,
                'accessToken' => $token,
                'tokenType' => 'Bearer',
                
            ];

            $dataToLog = [
                'causer_id' => auth()->user()->id,
                'action_id' => $user->id,
                'action_type' => "Models\User",
                'log_name' => "User logged in successfully",
                'description' => "{$user->firstname} {$user->lastname} logged in successfully",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            Notification::route('mail', $user->email)->notify(new LoginNotification($user));

            return JsonResponser::send(false, 'You are logged in successfully', $data);
       } catch (\Throwable $error) {
            return JsonResponser::send(true, $error->getMessage(), [], 500);
       }
    }

    /**
     * Get the authenticated User and permissions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateToken(Request $request)
    {
        
        try {
            if (! $token = JWTAuth::parseToken()) {

                return 111;
            }

            return response()->json([
                "success" => true,
                "message" => "Authorised!",
                "permission" => DB::table('permission_user')->where('user_id', auth()->user()->id)->get(),
                "userInstance" => auth()->user()->test,
                "user" => auth()->user(),
                "token" => $request->header('Authorization')
            ], 200);

        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return response()->json([
                    "success" => false,
                    "message" => "Invalid Token :(",
                    "data" => []
                ], 401);
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                return response()->json([
                    "success" => false,
                    "message" => "Token Expired :(",
                    "data" => []
                ], 401);
            } else if ( $e instanceof \Tymon\JWTAuth\Exceptions\JWTException) {
                return response()->json([
                    "success" => false,
                    "message" => "Error occured trying to authenticate user :(",
                    "data" => []
                ], 401);
            }else{
                return response()->json([
                    "success" => false,
                    "message" => $e->getMessage()." :(",
                    "data" => []
                ], 401);
            }
        }

    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();
        return JsonResponser::send(false, 'You are logged out successfully', []);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => auth()->user(),
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
