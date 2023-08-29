<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use SbscPackage\Ecommerce\Interfaces\RoleInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RegisterController extends BaseController
{
   
}
