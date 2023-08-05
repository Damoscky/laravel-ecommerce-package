<?php

use App\Http\Controllers\v1\Auth\RegisterController;
use App\Http\Controllers\v1\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(["prefix" => "v1"], function () {
    /** Cache */
    Route::get('/clear-cache', function () {
        Artisan::call('optimize:clear');
        return "Cache is cleared";
    });

    //Authentication Route
    Route::group(["prefix" => "auth"], function () {
        Route::post('register', [RegisterController::class, 'store']);
        Route::post('login', [LoginController::class, 'login']);
    });

    Route::group(['middleware' => ['auth:api', 'core']], function () {

        //Logout Route
        Route::get('auth/logout', [LoginController::class, 'logout']);

         //This route is use to validate api token from other services\
         Route::get('/validate/token', [LoginController::class, 'validateToken']);

        //This route is used for generating application key
        Route::get('/key', function() {
            return \Illuminate\Support\Str::random(32);
        });
    });

});
