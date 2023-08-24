<?php

use SbscPackage\Authentication\Http\Controllers\v1\Auth\RegisterController;
use SbscPackage\Authentication\Http\Controllers\v1\Auth\LoginController;
use SbscPackage\Authentication\Http\Controllers\v1\File\FileController;
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

Route::group(["prefix" => "v1", 'middleware' => ['auth']], function () {
    /** Cache */
    Route::get('/clear-cache', function () {
        Artisan::call('optimize:clear');
        return "Cache is cleared";
    });

});
