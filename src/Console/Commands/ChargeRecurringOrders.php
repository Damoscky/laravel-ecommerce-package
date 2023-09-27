<?php

namespace SbscPackage\Ecommerce\App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Order\OrderController;
use SbscPackage\Ecommerce\Http\Controllers\v1\Customer\OrderController as CustomerOrderController;
use SbscPackage\Ecommerce\Interfaces\GeneralStatusInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use SbscPackage\Ecommerce\Models\EcommerceBillingDetails;
use SbscPackage\Ecommerce\Models\EcommerceOrder;
use SbscPackage\Ecommerce\Models\EcommerceOrderDetails;
use SbscPackage\Ecommerce\Models\EcommerceProductSubscription;
use SbscPackage\Ecommerce\Models\EcommerceShippingAddress;
use SbscPackage\Ecommerce\Notifications\CustomerOrderNotification;
use SbscPackage\Ecommerce\Notifications\VendorOrderNotification;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use SbscPackage\Ecommerce\Services\Paystack;
use SbscPackage\Ecommerce\Services\Transactions\Transactions;

class ChargeRecurringOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'charge-recurring-order:daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charge Recurring Order';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
	 * Cancel Order for failed transaction
	 */
	protected function handleFailedTransactions($orderId, $data)
	{
		$order = EcommerceOrder::where("id", $orderId)->with("ecommerceorderdetails")->first();

		// update order status
		$order->update([
			"status" => "Cancelled",
			"payment_status" => $data["status"],
			"payment_method" => $data["channel"],
		]);

		// update single order
		foreach ($order->ecommerceorderdetails as $orderdetails) {
			$orderdetails->update([
				"payment_status" => $data["status"],
				"status" => "Cancelled"
			]);
			$orderdetails->ecommercehistories()->create([
				"status" => "CANCELLED"
			]);

			// update product with
			$product = $orderdetails->product;

			$product->update([
				'in_stock' => true,
				'quantity_purchased' => intval($product->quantity_purchased - $orderdetails->quantity_ordered),
				"available_quantity" => $product->available_quantity + $orderdetails->quantity_ordered
			]);
		}
	}

    /**
	 * Process Paystack Payment
	 */
	protected function paymentProcessing($orderId, $channel)
	{
		// eager load all the necessary relationship
		$order = EcommerceOrder::where("id", $orderId)
			->with("ecommerceorderdetails")
			->with("user")
			->first();

		if ($order->payment_status !== "paid") {

			$order->update([
				"status" => "Processing",
				"payment_status" => "Successful",
				"payment_method" => $channel
			]);
			$shippingaddress = EcommerceShippingAddress::where("ecommerce_order_id", $order->id)->first();
			$address = $shippingaddress->address;
			$user = $order->user;
			$phoneno = $shippingaddress->phoneno;
			// update all order items an order histories
			foreach ($order->ecommerceorderdetails as $orderdetails) {

				$orderdetails->update([
					"payment_status" => "Successful",
					"status" => "Processing"
				]);
				$orderdetails->ecommercehistories()->create([
					"status" => "PENDING CONFIRMATION"
				]);
				// send mail to vendor
                $vendorData = $orderdetails->ecommerceproduct->ecommerceVendor;

                Notification::route('mail', $vendorData->email)->notify(new VendorOrderNotification($vendorData));
			}
			// send email to customer
			$orderemail = [
				'email' => $user->email,
				'name' => $user->firstname.' '.$user->firstname,
				'phoneno' => $phoneno,
				'address' => $address,
				'orderID' => $order->orderID,
				'subject' => "Your Order was Successful!",
				'orders' => $order,
				'orderdetails' => $order->orderdetails,
			];
			Notification::route('mail', $user->email)->notify(new CustomerOrderNotification($orderemail));
		}
	}

    /**
	 * Generate unique order id
	 */
	protected function generateUniqueId()
	{
		// generate unique numbers for order id
		$orderID = hexdec(bin2hex(openssl_random_pseudo_bytes(5)));
		$orderIdExist = EcommerceOrder::find($orderID);
		// if exist append the id of d existed copy to new one to make it unique
		if ($orderIdExist) {
			$orderID = $orderID . '' . $orderIdExist->id;
		}
		return $orderID;
	}

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {        
        try {
            $pendingSubscription = EcommerceProductSubscription::where('status', GeneralStatusInterface::ACTIVE)
			->where('next_sub_date', Carbon::today())->where('end_date', '>=', Carbon::today())->get();

            if(count($pendingSubscription) == 0){
                return JsonResponser::send(true, "No record Available", [], 400);
            }
			DB::beginTransaction();

            foreach ($pendingSubscription as $subscription) {
				$interval = $subscription->interval;
				if($interval == "monthly"){
					$nextPeriod = Carbon::parse($subscription->last_sub_date)->addMonth(1);
				} elseif ($interval == "daily"){
					$nextPeriod = Carbon::parse($subscription->last_sub_date)->addDay(1);
				} elseif ($interval == "weekly"){
					$nextPeriod = Carbon::parse($subscription->last_sub_date)->addDay(7);
				} elseif ($interval == "quaterly"){
					$nextPeriod = Carbon::parse($subscription->last_sub_date)->addMonth(3);
				} elseif ($interval == "6months"){
					$nextPeriod = Carbon::parse($subscription->last_sub_date)->addMonth(6);
				}elseif ($interval == "yearly"){
					$nextPeriod = Carbon::parse($subscription->last_sub_date)->addYear(1);
				}

				//check if the next date is today

				$currentUserInstance = $subscription->user;
                $auth_code = $subscription->auth_code;
                $amount = ($subscription->ecommerceproduct->sales_price * $subscription->quantity) + $subscription->ecommerceproduct->shipping_fee;
				$total_shipping_fee = $subscription->ecommerceproduct->shipping_fee;

				$orderID = $this->generateUniqueId();

                $request = [
                    "authorization_code" => $auth_code, 
                    "email" => $subscription->user->email,
                    "amount" =>  ($amount * 100) * $subscription->quantity
                ];

				$order = EcommerceOrder::create([
					'fullname' => $currentUserInstance->firstname . ' ' . $currentUserInstance->lastname,
					'user_id' => $currentUserInstance->id,
					'email' => $currentUserInstance->email,
					'shipping_fee' => $total_shipping_fee,
					'payment_method' => "Paystack",
					'total_price' => $amount,
					'orderID' => $orderID,
					'orderNO' => $orderID,
				]);
				// add items order
				$orderdetails = EcommerceOrderDetails::firstOrCreate([
					'ecommerce_order_id' => $order->id,
					'user_id' => $currentUserInstance->id,
					'orderNO' => $orderID,
					'ecommerce_product_id' => $subscription->ecommerceproduct->id,
					'product_name' => $subscription->ecommerceproduct->product_name,
					'category' => $subscription->ecommerceproduct->category->name,
					'sub_category' => $subscription->ecommerceproduct->subcategory->name,
					'brand_name' => $subscription->ecommerceproduct->brand_name,
					'image' => $subscription->ecommerceproduct->product_image1,
					'status' => "Pending",
					'shipping_fee' => $subscription->ecommerceproduct->shipping_fee,
					'quantity_ordered' => $subscription->quantity,
					'unit_price' => $subscription->ecommerceproduct->sales_price,
					'sku' => $subscription->ecommerceproduct->sku,

				]);

				if(isset($currentUserInstance->userbilling)){
					// add billing address
					EcommerceBillingDetails::create([
						'fullname' => $currentUserInstance->userbilling->firstname . ' ' . $currentUserInstance->userbilling->lastname,
						'email' => $currentUserInstance->email,
						'ecommerce_order_id' => $order->id,
						'phoneno' => $currentUserInstance->userbilling->phoneno,
						'address' => $currentUserInstance->userbilling->address,
						'country' => $currentUserInstance->userbilling->country,
						'state' => $currentUserInstance->userbilling->state,
						'city' => $currentUserInstance->userbilling->city,
						'postal_code' => $currentUserInstance->userbilling->postal_code,
						'order_note' => $currentUserInstance->userbilling->order_note
					]);
				}
				if(isset($currentUserInstance->usershipping)){
					// add billing address
					EcommerceShippingAddress::create([
						'fullname' => $currentUserInstance->usershipping->firstname . ' ' . $currentUserInstance->usershipping->lastname,
						'email' => $currentUserInstance->email,
						'ecommerce_order_id' => $order->id,
						'phoneno' => $currentUserInstance->usershipping->phoneno,
						'address' => $currentUserInstance->usershipping->address,
						'country' => $currentUserInstance->usershipping->country,
						'state' => $currentUserInstance->usershipping->state,
						'city' => $currentUserInstance->usershipping->city,
						'postal_code' => $currentUserInstance->usershipping->postal_code,
					]);
				}else{
					EcommerceShippingAddress::create([
						'fullname' => $currentUserInstance->userbilling->firstname . ' ' . $currentUserInstance->userbilling->lastname,
						'email' => $currentUserInstance->email,
						'ecommerce_order_id' => $order->id,
						'phoneno' => $currentUserInstance->userbilling->phoneno,
						'address' => $currentUserInstance->userbilling->address,
						'country' => $currentUserInstance->userbilling->country,
						'state' => $currentUserInstance->userbilling->state,
						'city' => $currentUserInstance->userbilling->city,
						'postal_code' => $currentUserInstance->userbilling->postal_code,
					]);
				}

                $result = Paystack::chargeAuthorization($request);

				if($result['status'] != "success"){
                	// reverse the product and set order has cancelled
					$this->handleFailedTransactions($order->id, $result["data"]);
					DB::commit();
					return JsonResponser::send(true, "Transaction not successful", [], 400);
				}
				$card = $this->card($result);

				$trx = [
					"card_id" => $card->id,
					"user_id" => $currentUserInstance->id,
					"paidAt" => Carbon::parse($result["data"]["transaction_date"])->format("Y-m-d H:i:s"),
					"initializedAt" => Carbon::parse($result["data"]["transaction_date"])->format("Y-m-d H:i:s")
				];
				$transactionData = array_merge($result["data"], $trx);
				Transactions::create($transactionData);

				$this->paymentProcessing($order->id, $result["data"]["channel"]);

				$subscription->update([
					'last_sub_date' => Carbon::today(),
					'next_sub_date' => $nextPeriod
				]);

				$dataToLog = [
					'causer_id' => $currentUserInstance->id,
					'action_id' => $order->id,
					'action_type' => "Models\EcommerceOrder",
					'log_name' => "Payment was Successful",
                    'action' => 'Create',
					'description' => "Recurring Ordered was Successful by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
				];

				ProcessAuditLog::storeAuditLog($dataToLog);

				DB::commit();
			
            }
			$newOrder = EcommerceOrder::with('ecommerceorderdetails')->where('id', $order->id)->first();

			return JsonResponser::send(false, "Recurring Ordered Successfully! Order details has been sent to your email", $newOrder, 200);


        } catch (\Throwable $error) {
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }
}