<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Customer;
use Illuminate\Support\Facades\Validator;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use Illuminate\Http\Request;
use SbscPackage\Ecommerce\Models\EcommerceProduct;
use SbscPackage\Ecommerce\Models\EcommerceProductSubscription;
use Illuminate\Support\Facades\Notification;
use SbscPackage\Ecommerce\Notifications\CustomerSubscriptionNotification;
use SbscPackage\Ecommerce\Helpers\UserMgtHelper;
use Illuminate\Routing\Controller as BaseController;
use SbscPackage\Ecommerce\Services\Paystack;
use Carbon\Carbon;
use SbscPackage\Ecommerce\Interfaces\OrderStatusInterface;
use SbscPackage\Ecommerce\Models\EcommerceCard;
use SbscPackage\Ecommerce\Models\EcommerceOrderDetails;

class SubscriptionController extends BaseController
{
    public function store(Request $request)
    {
        /**
         * Validate Request
         */
        $validate = $this->validateRequest($request);
        /**
         * if validation fails
         */
        if ($validate->fails()) {
            return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all(), 400);
        }

        $interval = $request->interval;
        if($interval == "monthly"){
            $nextPeriod = Carbon::parse($request->start_date)->addMonth(1);
        } elseif ($interval == "daily"){
            $nextPeriod = Carbon::parse($request->start_date)->addDay(1);
        } elseif ($interval == "weekly"){
            $nextPeriod = Carbon::parse($request->start_date)->addDay(7);
        } elseif ($interval == "quaterly"){
            $nextPeriod = Carbon::parse($request->start_date)->addMonth(3);
        } elseif ($interval == "6months"){
            $nextPeriod = Carbon::parse($request->start_date)->addMonth(6);
        }elseif ($interval == "yearly"){
            $nextPeriod = Carbon::parse($request->start_date)->addYear(1);
        }

        $currentUserInstance = UserMgtHelper::userInstance();

        $orderDetailsId = $request->order_details_id;
        if(is_array($orderDetailsId)){
            foreach ($orderDetailsId as $orderId) {
                $ecommerceOrderDetails = EcommerceOrderDetails::find($orderId);
                if (is_null($ecommerceOrderDetails)) {
                    return JsonResponser::send(true, "Record not found", [], 400);
                }

                $transaction = EcommerceCard::where('user_id', $currentUserInstance->id)->where('email', $currentUserInstance->email)->first();

                if(is_null($transaction)){
                    return JsonResponser::send(true, "Transaction not found", [], 400);
                }

                $subscription = EcommerceProductSubscription::create([
                    'ecommerce_product_id' => $ecommerceOrderDetails->ecommerce_product_id,
                    'ecommerce_order_details_id' => $orderId,
                    'user_id' => $currentUserInstance->id,
                    'auth_code' => $transaction->authorization_code,
                    'interval' => $request->interval,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'quantity' => $ecommerceOrderDetails->ecommerceProduct->minimum_purchase_per_quantity,
                    'last_sub_date' => $request->start_date,
                    'next_sub_date' => $nextPeriod,
                ]);

                $dataToLog = [
                    'causer_id' => $currentUserInstance->id,
                    'action_id' => $subscription->id,
                    'action_type' => "Models\EcommerceProductSubscription",
                    'log_name' => "EcommerceProductSubscription created Successfully",
                    'action' => 'Create',
                    'description' => "Ecommerce Product Subscription created Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
                ];

                ProcessAuditLog::storeAuditLog($dataToLog);

                $data = [
                    'name' => $currentUserInstance->firstname,
                    'subscription' => $subscription,
                    'ecommerceproduct' => $subscription->ecommerceproduct
                ];

                Notification::route('mail', $currentUserInstance->email)->notify(new CustomerSubscriptionNotification($data));
            }
        }

		return JsonResponser::send(false, "Recurring order created successfully", $subscription, 200);

    }

    public function index(Request $request)
    {
        $orderId = $request->order_id;

        $currentUserInstance = UserMgtHelper::userInstance();

        $records = EcommerceProductSubscription::with('ecommerceorderdetails')
        ->when($orderId, function ($query, $orderId){
            return $query->whereHas('ecommerceorderdetails', function ($query) use ($orderId) {
                return $query->where('orderNO', $orderId);
            });
        })->where('user_id', $currentUserInstance->id)->paginate(10);

        return JsonResponser::send(false, "Record found successfully", $records, 200);

    }

     /**
     * validation
     */
    public function validateRequest(Request $request)
    {
        $rules = [
            "order_details_id" => 'required',
            // "auth_code" => 'required',
            "interval" => 'required',
            "start_date" => "required",
            "end_date" => "required",
            // "quantity" => "required",
        ];

        $validateProduct = Validator::make($request->all(), $rules);
        return $validateProduct;
    }

    public function chargeCustomer()
    {
        try {
            $pendingSubscription = EcommerceProductSubscription::get();

            if(count($pendingSubscription) == 0){
                return JsonResponser::send(true, "No record Available", [], 400);
            }

            foreach ($pendingSubscription as $subscription) {
                $auth_code = $subscription->auth_code;
                $amount = ($subscription->ecommerceproduct->sales_price * $subscription->quantity) + $subscription->ecommerceproduct->shipping_fee;

                $request = [
                    "authorization_code" => $auth_code, 
                    "email" => $subscription->user->email,
                    "amount" =>  $amount
                ];

                $subscribe = Paystack::chargeAuthorization($request);
            }
            if($subscribe['status'] == "success"){
                
            }

        } catch (\Throwable $error) {
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }


    public function show($id)
    {
        $record = EcommerceProductSubscription::with('ecommerceproduct', 'ecommerceorderdetails.ecommerceorder')->where('id', $id)->first();
        if(is_null($record)){
            return JsonResponser::send(true, "No record found", [], 400);
        }
        return JsonResponser::send(false, "Record found successfully", $record, 200);
    }

    public function cancelSubscription(Request $request, $id)    
    {
        try {
            $record = EcommerceProductSubscription::with('ecommerceproduct', 'ecommerceorderdetails.ecommerceorder')->where('id', $id)->first();
            if(is_null($record)){
                return JsonResponser::send(true, "Record not found", null, 400);
            }
            $currentUserInstance = UserMgtHelper::userInstance();

			DB::beginTransaction();
            
            $record->update([
                'status' => "Inactive",
                'cancel_description' => $request->cancel_description,
                'cancel_reason' => $request->cancel_reason,
            ]); 

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $record->id,
                'action_type' => "Models\EcommerceProductSubscription",
                'log_name' => "Subscription cancelled Successfully",
                'action' => 'Update',
                'description' => "Subscription cancelled Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Subscription has been cancelled successfully', $record, 200);

        } catch (\Throwable $error) {
            logger($error);
            DB::rollback();
            return JsonResponser::send(true, $error->getMessage(), null, 500);
        }
    }

    public function updateSubscription(Request $request, $id)    
    {
        try {
            $record = EcommerceProductSubscription::with('ecommerceproduct', 'ecommerceorderdetails.ecommerceorder')->where('id', $id)->first();
            if(is_null($record)){
                return JsonResponser::send(true, "Record not found", null, 400);
            }
            $currentUserInstance = UserMgtHelper::userInstance();

			DB::beginTransaction();
            
            $record->update([
                'interval' => $request->inteval,
            ]); 

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $record->id,
                'action_type' => "Models\EcommerceProductSubscription",
                'log_name' => "Subscription updated Successfully",
                'action' => 'Update',
                'description' => "Subscription updated Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Subscription has been updated successfully', $record, 200);

        } catch (\Throwable $error) {
            logger($error);
            DB::rollback();
            return JsonResponser::send(true, $error->getMessage(), null, 500);
        }
    }

}