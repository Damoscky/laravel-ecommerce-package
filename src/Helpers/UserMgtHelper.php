<?php

namespace SbscPackage\Ecommerce\Helpers;

use Auth;
use Illuminate\Support\Facades\DB;

class UserMgtHelper
{


    //Get User id
    public static function userInstance()
    {
        $userInstance = Auth::user();
        return $userInstance;
    }
}
