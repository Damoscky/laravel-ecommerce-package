<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Customer;

use SbscPackage\Ecommerce\Models\EcommerceBillingDetails;
use SbscPackage\Ecommerce\Models\EcommerceShippingAddress;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Models\Card;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\User;
use SbscPackage\Ecommerce\Models\EcommerceUserShipping;
use SbscPackage\Ecommerce\Models\EcommerceUserBilling;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use SbscPackage\Ecommerce\Helpers\UserMgtHelper;
use Illuminate\Support\Facades\Storage;
use SbscPackage\Ecommerce\Helpers\FileUploadHelper;
use SbscPackage\Ecommerce\Models\Category;
use SbscPackage\Ecommerce\Models\EcommerceCart;
use SbscPackage\Ecommerce\Services\Paystack;
use SbscPackage\Ecommerce\Models\EcommerceProduct;
use Illuminate\Support\Facades\Notification;
use SbscPackage\Ecommerce\Notifications\VendorOrderNotification;
use SbscPackage\Ecommerce\Notifications\CustomerOrderNotification;
use Illuminate\Support\Facades\Validator;
use Hash, DB;
use Carbon\Carbon;
use SbscPackage\Ecommerce\Interfaces\GeneralStatusInterface;
use SbscPackage\Ecommerce\Interfaces\OrderStatusInterface;
use SbscPackage\Ecommerce\Interfaces\PaymentStatusInterface;
use SbscPackage\Ecommerce\Models\EcommerceCard;
use SbscPackage\Ecommerce\Models\EcommerceComplaint;
use SbscPackage\Ecommerce\Models\EcommerceOrder;
use SbscPackage\Ecommerce\Models\EcommerceOrderDetails;
use SbscPackage\Ecommerce\Models\EcommerceProductSubscription;
use SbscPackage\Ecommerce\Models\EcommerceTransaction;
use SbscPackage\Ecommerce\Services\Transactions\Transactions;

class OrderController extends BaseController
{

    /**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		try {
			$currentUserInstance = UserMgtHelper::userInstance();
			$userId = $currentUserInstance->id;
            $orderNoSearchParam = $request->orderNo;

			$orders = EcommerceOrder::when($orderNoSearchParam, function ($query) use ($orderNoSearchParam) {
                return $query->where('orderID', 'LIKE', '%' . $orderNoSearchParam . '%');
            })->latest()->where('user_id', $userId)->with('ecommerceorderdetails', 'ecommerceshippingaddress', 'ecommerceorderdetails', 'user')->paginate(10);

			return JsonResponser::send(false, "Record found successfully", $orders, 200);
		} catch (\Throwable $error) {
			return JsonResponser::send(true, $error->getMessage(), []);
		}
	}

    /**
	 * Order Details
	 */
	public function orderdetails($id)
	{

		$userInstance = UserMgtHelper::userInstance();
		$userId = $userInstance->id;

		try {
			$currentUserInstance = UserMgtHelper::userInstance();
			$userId = $currentUserInstance->id;

			$orders = EcommerceOrder::where('id', $id)->where('user_id', $userId)->with('ecommerceorderdetails', 'ecommerceshippingaddress', 'ecommercebillingdetails', 'ecommerceorderdetails', 'user')->first();

			return JsonResponser::send(false, "Record found successfully", $orders, 200);
		} catch (\Throwable $error) {
			return JsonResponser::send(true, $error->getMessage(), []);
		}
	}

    /**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function dashboard()
	{
		try {
			$currentUserInstance = UserMgtHelper::userInstance();
			$userId = $currentUserInstance->id;

            $transaction = EcommerceTransaction::where('user_id', $userId)->sum('amount');
            $totalOrder = EcommerceOrder::latest()->where('user_id', $userId)->count();
			$activeSubscription = EcommerceProductSubscription::where('status', GeneralStatusInterface::ACTIVE)->count();

			$orders = EcommerceOrder::latest()->where('user_id', $userId)->with('ecommerceorderdetails', 'ecommerceshippingaddress', 'ecommerceorderdetails', 'user')->take(5)->get();

            $data = [
                'totaltransaction' => $transaction,
                'totalOrders' => $totalOrder,
                'activeSubscription' => $activeSubscription,
                'orders' => $orders
            ];
			return JsonResponser::send(false, "Record found successfully", $data, 200);
		} catch (\Throwable $error) {
			return JsonResponser::send(true, $error->getMessage(), []);
		}
	}

    /**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		/**
		 * Validate Data
		 */
		$validate = $this->validateOrder($request);
		/**
		 * if validation fails
		 */
		if ($validate->fails()) {
			return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all(), 400);
		}

		DB::beginTransaction();

		// Check items condition when there are less items available to purchase
		if ($this->productsAreNoLongerAvailable()) {
			return JsonResponser::send(true, "One of the items in your cart is no longer available.", [], 400);
		}

		$currentUserInstance = UserMgtHelper::userInstance();
		$user = $currentUserInstance->id;

		$orderID = $this->generateUniqueId();

		try {

			$order = EcommerceOrder::create([
				'fullname' => $currentUserInstance->firstname . ' ' . $currentUserInstance->lastname,
				'user_id' => $currentUserInstance->id,
				'email' => $currentUserInstance->email,
				'shipping_fee' => $request->total_shipping_fee,
				'payment_method' => $request->payment_method,
				'payment_gateway' => $request->payment_gateway,
				'total_price' => $request->total_price,
				'orderID' => $orderID,
				'orderNO' => $orderID,
			]);

			
			// add billing address
			EcommerceBillingDetails::create([
				'fullname' => $request->billing_firstname . ' ' . $request->billing_lastname,
				'email' => $request->billing_email,
				'ecommerce_order_id' => $order->id,
				'phoneno' => $request->billing_phoneno,
				'address' => $request->billing_address,
				'country' => $request->billing_country,
				'state' => $request->billing_state,
				'city' => $request->billing_city,
				'postal_code' => $request->billing_postal_code,
                'order_note' => $request->billing_order_note
			]);
			// add shipping
			if ($request->diffrent_address == 1) {
				$shippingaddress = EcommerceShippingAddress::create([
					'fullname' => $request->shiiping_firstname.' '.$request->shiiping_lastname,
					'phoneno' => $request->shipping_phoneno,
					'ecommerce_order_id' => $order->id,
					'address' => $request->shipping_address,
					'email' => $request->shipping_email,
					'country' => $request->shipping_country,
					'state' => $request->shipping_state,
					'city' => $request->shipping_city,
					'postal_code' => $request->shipping_postal_code,
				]);
			} else {
				$shippingaddress = EcommerceShippingAddress::create([
					'fullname' => $request->billing_firstname . ' ' . $request->billing_lastname,
                    'email' => $request->billing_email,
                    'ecommerce_order_id' => $order->id,
                    'phoneno' => $request->billing_phoneno,
                    'address' => $request->billing_address,
                    'country' => $request->billing_country,
                    'state' => $request->billing_state,
                    'city' => $request->billing_city,
                    'postal_code' => $request->billing_postal_code,
				]);
			}

			$userCarts = EcommerceCart::where("user_id", $currentUserInstance->id)->get();
			foreach ($request->carts as $cart) {
				$product = EcommerceProduct::find($cart['ecommerce_product_id']);

				// add items order
				$orderdetails = EcommerceOrderDetails::firstOrCreate([
					'ecommerce_order_id' => $order->id,
					'user_id' => $currentUserInstance->id,
					'orderNO' => $orderID,
					'ecommerce_product_id' => $product->id,
					'product_name' => $product->product_name,
					'category' => $product->category->name,
					'sub_category' => $product->subcategory->name,
					'brand_name' => $product->brand_name,
					'image' => $product->product_image1,
					'status' => "Pending",
					'shipping_fee' => $product->shipping_fee,
					'quantity_ordered' => $cart['quantity'],
					'unit_price' => $cart['price'],
					'sku' => $product->sku,

				]);

				$orderdetails->ecommercehistories()->create([
					"status" => "ORDER PLACED"
				]);

				// update product
				$product->update([
					'in_stock' => $product->available_quantity > $cart['quantity'],
					'quantity_purchased' => intval($product->quantity_purchased + $cart['quantity']),
					"available_quantity" => $product->available_quantity - $cart['quantity']
				]);

				/*** CLEAR CART ***/
				$cartRec = EcommerceCart::where('user_id', $currentUserInstance->id)->where('ecommerce_product_id', $cart['ecommerce_product_id'])->first();

				$cartRec->delete();
			}


			// card
			if ($request->payment_method === "card") {

				// validate reference number
				if ($request->payment_gateway === "Paystack") {
					$paystack = new Paystack();
					$result = $paystack->getPaymentData();

					if ($result["data"]["status"] !== "success") {

						// reverse the product and set order has cancelled
						$this->handleFailedTransactions($order->id, $result["data"]);
						DB::commit();
						return JsonResponser::send(true, "Transaction not successful", [], 400);
					}
                    $card = $this->card($result);

					$trx = [
						"card_id" => $card->id,
						"user_id" => auth()->user()->id,
						"order_id" => $order->id,
						"paidAt" => Carbon::parse($result["data"]["paid_at"])->format("Y-m-d H:i:s"),
						"initializedAt" => Carbon::parse($result["data"]["created_at"])->format("Y-m-d H:i:s")
					];
					$transactionData = array_merge($result["data"], $trx);
					Transactions::create($transactionData);

					$this->paymentProcessing($order->id, $result["data"]["channel"]);
				}

				if ($request->payment_gateway === "Stripe") {
					$result = $this->validateStripePayment($request->payment_intent);

					if ($result['status'] !== 'succeeded') {

						// reverse the product and set order has cancelled
						$this->handleStripeFailedTransactions($order->id);

						DB::commit();

						return JsonResponser::send(true, "Transaction not successful", [], 400);
					}
                   

					$this->paymentProcessing($order->id, $result['charges']['data'][0]['payment_method_details']['type']);
					// $this->card($paymentData);

				}

				// DB::commit();

				$dataToLog = [
					'causer_id' => $currentUserInstance->id,
					'action_id' => $order->id,
					'action_type' => "Models\EcommerceOrder",
					'log_name' => "Payment was Successful",
                    'action' => 'Create',
					'description' => "Request Ordered Payment was Successful by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
				];

				ProcessAuditLog::storeAuditLog($dataToLog);

				DB::commit();
				$newOrder = EcommerceOrder::with('ecommerceorderdetails')->where('id', $order->id)->first();

                return JsonResponser::send(false, "Request Ordered Successfully! Order details has been sent to your email", $newOrder, 200);
			}

			// on delivery payment
			if ($request->payment_method === "cash_on_delivery") {
				$address = $request->shipping_address ?? $request->billing_address;
				$orderemail = [
					'email' => $currentUserInstance->email,
					'name' => $shippingaddress->fullname,
					'phoneno' => $request->sphoneno ?? $currentUserInstance->phoneno,
					'address' => $address,
					'orderID' => $orderID,
					'subject' => "Your Order was Successful!",
					'orders' => $order,
					'orderdetails' => $order->ecommerceorderdetails,
				];

                Notification::route('mail', $currentUserInstance->email)->notify(new CustomerOrderNotification($orderemail));

				foreach ($order->ecommerceorderdetails as $orderdetails) {
                    $orderdetails->update([
                        "payment_status" => "Pending",
                        "status" => "Processing"
                    ]);
                    $orderdetails->ecommercehistories()->create([
                        "status" => "PENDING CONFIRMATION"
                    ]);
                    // send mail to vendor
                    $vendorData = $orderdetails->ecommerceproduct->ecommerceVendor->user;
    
                    Notification::route('mail', $vendorData->email)->notify(new VendorOrderNotification($vendorData));

					
				}
			}

			DB::commit();

			$currentUserInstance = auth()->user();

			$dataToLog = [
				'causer_id' => auth()->user()->id,
				'action_id' => $order->id,
				'action_type' => "Models\EcommerceOrder",
				'log_name' => "Request Ordered Successfully",
                'action' => 'Create',
				'description' => "Request Ordered Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
			];

			ProcessAuditLog::storeAuditLog($dataToLog);
			return JsonResponser::send(false, "Request Ordered Successfully! Order details has been sent to your email", $order, 200);
		} catch (\Throwable $error) {
			DB::rollBack();
// 			Log::error($error);
			return JsonResponser::send(true, $error->getMessage(), [], 500);
		}
	}

	public function chargeCustomer()
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

	protected function handleStripeFailedTransactions($orderId)
	{
		$order = EcommerceOrder::where("id", $orderId)->with("ecommerceorderdetails")->first();

		// update order status
		$order->update([
			"status" => "Cancelled",
			"payment_status" => "Failed",
			"payment_method" => "Card",
		]);

		// update single order
		foreach ($order->orderdetails as $orderdetails) {
			$orderdetails->update([
				"payment_status" => "Failed",
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
	 * Paystack webhook
	 */
	public function webhooks(Request $request)
	{
		// Retrieve the request's body
		$paystackData = $request->getContent();

		$secretKey = env("PAYSTACK_SECRET_KEY");

		// validate event do all at once to avoid timing attack
		if ($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] !== hash_hmac('sha512', $paystackData, $secretKey))
			exit();
		$paymentData = json_decode($paystackData, true);

		if ($paymentData["event"] === "charge.success") {
			if ($paymentData["data"]["metadata"]["orderId"] && $paymentData["data"]["metadata"]["user_id"]) {
				// store card details for recuring payment
				$this->card($paymentData);
				// process payment
				$this->paymentProcessing($paymentData["data"]["metadata"]["orderId"], $paymentData["data"]["channel"]);
			}
		}

		return response()->json([], 200);
	}

	/**
	 * calculate cart worth
	 * WIP
	 */
	protected function calculateCartWorth()
	{
		$userCarts = EcommerceCart::where("user_id", auth()->user()->id)->with("product")->get();
		$total_shipping_fee = 0;
		$total_price = 0;
		foreach ($userCarts as $cart) {
			$product = $cart->product;
			$total_price += floatval($product->new_price * $cart->quantity);
			$total_shipping_fee += floatval($product->shipping_fee1 * $cart->quantity);
		}
		return [
			"total_shipping_fee" => $total_shipping_fee,
			"total_price" => $total_price
		];
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
	 * Product validation
	 */
	protected function productsAreNoLongerAvailable()
	{
        $userInstance = UserMgtHelper::userInstance();
        $userId = $userInstance->id;
		// eager load user carts with products for performance sake
		$userCarts = EcommerceCart::where("user_id", $userId)->with("ecommerceproduct")->get();
		// validate empty user cart
		if ($userCarts->isEmpty()) {
			return true;
		}
		// validate product quantity against order quantity
		foreach ($userCarts as $cart) {
			$product = $cart->ecommerceproduct;
			if ($product->available_quantity < $cart->quantity) {
				return true;
			}
		}

		return false;
	}

	public function cancelOrder(Request $request, $id)    
    {
        try {
            $record = EcommerceOrderDetails::find($id);
            if(is_null($record)){
                return JsonResponser::send(true, "Record not found", null, 400);
            }
            $currentUserInstance = UserMgtHelper::userInstance();

			DB::beginTransaction();
            //check order status
            if(($record->status == OrderStatusInterface::PENDING) || ($record->status == OrderStatusInterface::PROCESSING)){
                $record->update([
                    'status' => OrderStatusInterface::CANCELLED,
					'cancel_description' => $request->cancel_description,
					'cancel_reason' => $request->cancel_reason,
                ]); 

				//send email and refund customer
				if($record->payment_status == PaymentStatusInterface::SUCCESS && $record->ecommerceorder->payment_method == "card"){
					if($record->ecommerceorder->payment_gateway == "Paystack"){
						$paystackData = [
							'transaction' => isset($record->ecommerceorder->ecommercetransaction) ? $record->ecommerceorder->ecommercetransaction->reference : "",
							'amount' => ($record->unit_price * $record->quantity_ordered)  * 100,
						];
						$result = Paystack::refundTransaction($paystackData);
						$record->update([
                            'payment_status' => "Refund"
						]);
					}
					
					if($record->ecommerceorder->payment_gateway == "Stripe"){
					    
					}
				}

                $dataToLog = [
                    'causer_id' => $currentUserInstance->id,
                    'action_id' => $record->id,
                    'action_type' => "Models\EcommerceOrderDetails",
                    'log_name' => "Order cancelled Successfully",
                    'action' => 'Update',
                    'description' => "Order cancelled Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
                ];
    
                ProcessAuditLog::storeAuditLog($dataToLog);
				DB::commit();
                return JsonResponser::send(false, 'Order has been cancelled successfully', $record, 200);
            }

            return JsonResponser::send(true, 'Your Order could not be cancelled', $record, 400);
        } catch (\Throwable $error) {
            logger($error);
            DB::rollback();
            return JsonResponser::send(true, $error->getMessage(), null, 500);
        }
    }

	public function validateOrder($request)
	{
		$rules = [
			"total_shipping_fee" => "required|integer",
			"total_price" => "required",
			"payment_method" => "required|string|in:card,cash_on_delivery,bank_transfer",
			// "shipping_method" => "required|string|in:door_delivery,pick_up",
			"billing_phoneno" => "required|min:10|max:12",
			"billing_address" => "required|string|max:255",
			"billing_country" => "string|max:150",
			"billing_state" => "required|string|max:150",
			"billing_city" => "required|string|max:150",
			"diffrent_address" => "boolean",
		];

		if ($request->payment_gatway == "Paystack") {
			$rules["trxref"] = "required|max:250";
		}

		if ($request->payment_gatway == "Stripe") {
			$rules["payment_intent"] = "required|max:250";
		}

		// if ($request->payment_method == "mobile_money") {
		// 	$rules["phone"] = "required|min:10|max:12";
		// 	$rules["provider"] = "required|string|max:5|in:mtn,vod,tgo";
		// }

		if ($request->different_address == 1) {
			$rules["shipping_fullname"] = "required|string|max:255";
			$rules["shipping_phoneno"] = "required|string|min:10|max:12";
			$rules["shipping_address"] = "required|string|max:255";
			$rules["shipping_country"] = "string|max:150";
			$rules["shipping_state"] = "required|string|max:150";
			$rules["shipping_city"] = "required|string|max:150";
		}
		$validateOrder = Validator::make($request->all(), $rules);
		return $validateOrder;
	}

    /**
	 * Store users card
	 */
	protected function card($data)
	{
		$cardData = [
			"email" =>  $data["data"]["customer"]["email"],
			"user_id" => auth()->user()->id
		];
		$cardData = array_merge($cardData, $data["data"]["authorization"]);

		return EcommerceCard::firstOrCreate(
			[
				"email" => $cardData["email"],
				"signature" => $cardData["signature"]
			],
			[
				"user_id" => $cardData["user_id"],
				"authorization_code" => $cardData["authorization_code"],
				"bin" => $cardData["bin"],
				"last4" => $cardData["last4"],
				"exp_month" => $cardData["exp_month"],
				"exp_year" => $cardData["exp_year"],
				"channel" => $cardData["channel"],
				"card_type" => $cardData["card_type"],
				"bank" => $cardData["bank"],
				"country_code" => $cardData["country_code"],
				"brand" => $cardData["brand"],
				"reusable" => $cardData["reusable"],
			]
		);
	}

	/**
	 *Check Stock
	 */
	public function checkStock(Request $request)
	{
		try {
			$userCarts = EcommerceCart::where("user_id", auth()->user()->id)->with("ecommerceproduct")->get();
			// validate empty user cart
			if ($userCarts->isEmpty()) {
				return JsonResponser::send(true, 'Can not checkout an empty cart', null, 400);
			}

			foreach ($userCarts as $cart) {

				/*** Confirm Instock  ***/
				if (!$cart->ecommerceproduct->in_stock || $cart->ecommerceproduct->available_quantity < 1) {
					return JsonResponser::send(true, $cart->ecommerceproduct->product_name . ' is Out of Stock!', null, 400);
				}

				// validate product quantity against order quantity
				if ($cart->ecommerceproduct->available_quantity < $cart->quantity) {
					return JsonResponser::send(true, $cart->ecommerceproduct->product_name . ' only have ' . $cart->ecommerceproduct->available_quantity . ' quantity left', null, 400);
				}
			}
			return JsonResponser::send(false, 'Product Available', null, 200);
		} catch (\Throwable $error) {
			return JsonResponser::send(true, $error->getMessage(), []);
		}
	}



	public function stripePayment(Request $request)
	{
		$stripe = new \Stripe\StripeClient(
			env('STRIPE_SECRET')
		);

		$orderItems = array();

		foreach ($request->carts as $key => $cart) {
			$cartRecord = Product::where('id', $cart['ecommerce_product_id'])->first();
			$item = [
				'price_data' => [
					'currency' => 'usd',
					'product_data' => [
						'name' => isset($cartRecord) ? $cartRecord->product_name : '',
					],
					'unit_amount' => $cart['price'] * 100,
				],
				'quantity' => $cart['quantity'],
			];
			array_push($orderItems, $item);
		}

		$result = $stripe->checkout->sessions->create([
			'shipping_address_collection' => ['allowed_countries' => ['US', 'CA', 'NG', 'EG']],
			'shipping_options' => [
				[
					'shipping_rate_data' => [
						'type' => 'fixed_amount',
						'fixed_amount' => ['amount' => $request->total_shipping_fee * 100, 'currency' => 'usd'],
						'display_name' => 'Shipping Fee',
						'delivery_estimate' => [
							'minimum' => ['unit' => 'business_day', 'value' => 5],
							'maximum' => ['unit' => 'business_day', 'value' => 7],
						],
					],
				],
			],
			'invoice_creation' => ['enabled' => true],
			'line_items' => $orderItems,
			'mode' => 'payment',
			'success_url' => env('STRIPE_SUCCESS_URL') . '#/complete-order-placement',
			'cancel_url' => env('STRIPE_CANCEL_URL'),
		]);

		return JsonResponser::send(false, "Payment Processing Initiated Successfully", $result);
	}

	public function validateStripePayment($paymentIntent)
	{
		$stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

		return $result = $stripe->paymentIntents->retrieve($paymentIntent, []);
		// return $result['charges']['data'][0]['payment_method_details']['type'];
	}

	public function complaints(Request $request)
	{
        $searchParam = $request->complaint_id;

		$currentUserInstance = auth()->user();

		$records = EcommerceComplaint::when($searchParam, function ($query) use ($searchParam) {
			return $query->where('id', 'LIKE', '%' . $searchParam . '%');
		})->where('user_id', $currentUserInstance->id)->paginate(12);

		return JsonResponser::send(false, "Record found Successfully", $records, 200);

	}

	public function createComplain(Request $request)
	{
		try {
			/**
			 * Validate Data
			 */
			$validate = $this->validatComplainRequest($request);
			/**
			 * if validation fails
			 */
			if ($validate->fails()) {
				return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all(), 400);
			}
			DB::beginTransaction();
			
			$attachment = FileUploadHelper::singleStringFileUpload($request->attachment, "EcommerceComplain");
			
			$currentUserInstance = auth()->user();

			if(is_array($request->order_ids)){
				foreach ($request->order_ids as $orderId) {

					//check if order has been complained 
					$complaint = EcommerceComplaint::where('user_id', $currentUserInstance->id)
					->where('ecommerce_order_details_id', $orderId)->first();
					if(!is_null($complaint)){
						return JsonResponser::send(true, "One or more item has already been sent for complaint", [], 400);
					}

					$newComplain = EcommerceComplaint::create([
						'ecommerce_order_details_id' => $orderId,
						'user_id' => $currentUserInstance->id,
						'reason' => $request->reason,
						'customer_comment' => $request->comment,
						'attachment' => $attachment,
						'sales_officer' => $request->sales_officer,
						'is_active' => true
					]);

					$dataToLog = [
						'causer_id' => auth()->user()->id,
						'action_id' => $orderId,
						'action_type' => "Models\EcommerceComplaint",
						'log_name' => "Complaint Submitted Successfully",
						'action' => 'Create',
						'description' => "Complaint Request Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
					];
				}
			}

			DB::commit();

			return JsonResponser::send(false, "Complaint Request Submited Successfully", [], 200);

			
		} catch (\Throwable $th) {
			DB::rollBack();
			return JsonResponser::send(true, $th->getMessage(), [], 500);
		}
	}

	/**
     * Validate profile request
     */
    protected function validatComplainRequest($request)
    {
        $rules = [
			'order_ids' => 'required|array',
            'reason' => 'required|string',
            'comment' => 'required|string|min:5',
            'attachment' => 'required',
        ];


        $validatedData = Validator::make($request->all(), $rules);
        return $validatedData;
    }

}