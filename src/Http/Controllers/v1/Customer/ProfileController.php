<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Customer;

use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\User;
use SbscPackage\Ecommerce\Models\EcommerceUserShipping;
use SbscPackage\Ecommerce\Models\EcommerceUserBilling;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use SbscPackage\Ecommerce\Helpers\UserMgtHelper;
use Illuminate\Support\Facades\Storage;
use SbscPackage\Ecommerce\Helpers\FileUploadHelper;
use Validator, DB;

class ProfileController extends BaseController
{


    /**
     * Get Customer Profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function profile()
    {

        $currentUserInstance = UserMgtHelper::userInstance();
		$userId = $currentUserInstance->id;

        // $user =  User::with("userbilling", "usershipping")->find($userId);
        // return JsonResponser::send(false, "Record found successfully", $user, 200);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        /**
         * Validate Data
         */
        $validate = $this->validateProfile($request);
        /**
         * if validation fails
         */
        if ($validate->fails()) {
            return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all(), 400);
        }
        
        $user = UserMgtHelper::userInstance();
		$userId = $user->id;

        $image = $request->image;
        if(isset($request->image)){
            $imageUrl = FileUploadHelper::singleStringFileUpload($image, 'Profile');

        }else{
            $imageUrl = $user->image;
        }

        DB::beginTransaction();

        
        try {
           $updateProfile = $user->update([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'phoneno' => $request->phoneno,
                'image' => $imageUrl,
           ]);

            DB::commit();

            $user = $user->refresh();

            return JsonResponser::send(false, "Profile Updated successfully", $user, 200);

        } catch (\Throwable $error) {
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function updateBillingInfo(Request $request)
    {
        /**
         * Validate Data
         */
        $validate = $this->validateBillingInfo($request);
        /**
         * if validation fails
         */
        if ($validate->fails()) {
            return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all(), 400);
        }
        
        $user = UserMgtHelper::userInstance();
		$userId = $user->id;

        DB::beginTransaction();

        
        try {

            $record = EcommerceUserBilling::updateOrCreate([
                'user_id' => $userId,
            ],[
                'firstname' => $request->billing_firstname,
                'lastname' => $request->billing_lastname,
                'middlename' => $request->billing_middlename,
                'email' => $request->billing_email,
                'phoneno' => $request->billing_phoneno,
                'address' => $request->billing_address,
                'country' => $request->billing_country,
                'state' => $request->billing_state,
                'postal_code' => $request->billing_postal_code,
            ]);
            
            DB::commit();

            $user = $user->refresh();

            return JsonResponser::send(false, "Record Updated successfully", $record, 200);

        } catch (\Throwable $error) {
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function updateShippingInfo(Request $request)
    {
        /**
         * Validate Data
         */
        $validate = $this->validateShippingInfo($request);
        /**
         * if validation fails
         */
        if ($validate->fails()) {
            return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all(), 400);
        }
        
        $user = UserMgtHelper::userInstance();
		$userId = $user->id;

        DB::beginTransaction();

        
        try {

            $record = EcommerceUserShipping::updateOrCreate([
                'user_id' => $userId,
            ],[
                'firstname' => $request->shipping_firstname,
                'lastname' => $request->shipping_lastname,
                'middlename' => $request->shipping_middlename,
                'email' => $request->shipping_email,
                'phoneno' => $request->shipping_phoneno,
                'address' => $request->shipping_address,
                'country' => $request->shipping_country,
                'state' => $request->shipping_state,
                'postal_code' => $request->shipping_postal_code,
            ]);
            
            DB::commit();

            $user = $user->refresh();

            return JsonResponser::send(false, "Record Updated successfully", $record, 200);

        } catch (\Throwable $error) {
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    /**
     * Validate profile request
     */
    protected function validateProfile($request)
    {
        $rules = [
            'firstname' => 'string|max:255',
            'lastname' => 'string|max:255',
            'phoneno' => 'max:12|unique:users,phoneno,' . auth()->user()->id,
            // "image" => "string",
        ];


        $validatedData = Validator::make($request->all(), $rules);
        return $validatedData;
    }

    /**
     * Validate profile request
     */
    protected function validateBillingInfo($request)
    {
        $rules = [
            'billing_firstname' => 'required|string',
            'billing_lastname' => 'required|string',
            'billing_middlename' => 'required|string',
            'billing_phoneno' => 'required|string',
            'billing_address' => 'required|string|min:10',
            'billing_state' => 'required|string|max:150',
            'billing_city' => 'required|string|max:150',
            'billing_country' => 'string|max:150',
            'billing_postal_code' => 'string|max:150',
        ];


        $validatedData = Validator::make($request->all(), $rules);
        return $validatedData;
    }

    /**
     * Validate profile request
     */
    protected function validateShippingInfo($request)
    {
        $rules = [
            'billing_firstname' => 'required|string',
            'billing_lastname' => 'required|string',
            'billing_middlename' => 'required|string',
            'billing_phoneno' => 'required|string',
            'shipping_address' => 'required|string|min:10',
            'shipping_state' => 'required|string|max:150',
            'shipping_city' => 'required|string|max:150',
            'shipping_country' => 'string|max:150',
            'shipping_postal_code' => 'string|max:150',
        ];


        $validatedData = Validator::make($request->all(), $rules);
        return $validatedData;
    }
}
