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
use SbscPackage\Ecommerce\Models\Category;
use SbscPackage\Ecommerce\Models\EcommerceCart;
use SbscPackage\Ecommerce\Models\EcommerceProduct;
use Illuminate\Support\Facades\Validator;
use Hash, DB;

class CartController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $userInstance = UserMgtHelper::userInstance();

        $carts = EcommerceCart::where('user_id', $userInstance->id)->get();
        return JsonResponser::send(false, $carts->count() . ' Item(s) Found', $carts, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $userInstance = UserMgtHelper::userInstance();
        $userId = $userInstance->id;

        /**
         * Validate Data
         */
        $validate = $this->validateCart($request);

        /**
         * if validation fails
         */
        if ($validate->fails()) {
            return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all(), 400);
        }

        try {
            $product = EcommerceProduct::find($request->product_id);
            if (is_null($product)) {
                return JsonResponser::send(true, "Product Record not found. Invalid Product Id", [], 400);
            }
            if (!$product->in_stock || $product->available_quantity < 1) {
                return JsonResponser::send(true, "Product is Out of Stock!", [], 400);
            }
            DB::beginTransaction();

            $cartexist = EcommerceCart::where('ecommerce_product_id', $request->product_id)->where('user_id', $userId)->first();
            if ($cartexist) {
                $totalquantity = $cartexist->quantity + $request->quantity;
                $totalprice = $totalquantity * $request->price;
                if ($totalquantity > $product->available_quantity) {
                    return JsonResponser::send(true, "Can not add more than the available quantity!", [], 400);
                }

                $cart = $cartexist->update([
                    'quantity' => $totalquantity,
                    'price' => $request->price,
                    'total_price' => $totalprice,
                ]);
                $dataToLog = [
                    'causer_id' => $userInstance->id,
                    'action_id' => $cartexist->id,
                    'action_type' => "Models\EcommerceCart",
                    'log_name' => "Item added to Cart",
                    'action' => 'Create',
                    'description' => "Item added to Cart by {$userInstance->lastname} {$userInstance->firstname}",
                ];

                $newCarts = EcommerceCart::where('user_id', $userId)->where('id', $cartexist->id)->first();
            } else {
                $totalprice = $request->quantity * $request->price;
                $cart = EcommerceCart::create([
                    'user_id' => $userId,
                    'ecommerce_product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'price' => $request->price,
                    'size' => $request->size,
                    'color' => $request->color,
                    'total_price' => $totalprice,
                ]);


                $newCarts = EcommerceCart::where('user_id', $userId)->where('id', $cart->id)->first();

                $dataToLog = [
                    'causer_id' => $userInstance->id,
                    'action_id' => $cart->id,
                    'action_type' => "Models\EcommerceCart",
                    'log_name' => "Item added to Cart",
                    'action' => 'Create',
                    'description' => "Item added to Cart by {$userInstance->lastname} {$userInstance->firstname}",
                ];
            }

            ProcessAuditLog::storeAuditLog($dataToLog);

            DB::commit();

            return JsonResponser::send(false, "Item added to Cart!", $newCarts, 201);
        } catch (\Throwable $error) {
            DB::rollback();
            return JsonResponser::send(true, $error->getMessage(), []);
        }
    }

      /**
     * Login and transfer item to cart
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function transferCart(Request $request)
    {
        $currentUserInstance = UserMgtHelper::userInstance();
        $userId = $currentUserInstance->id;

        try {
            DB::beginTransaction();
            if (is_array($request->carts)) {
                foreach ($request->carts as $key => $values) {
                    $cartexist = EcommerceCart::where('ecommerce_product_id', $values["product_id"])->where('user_id', $userId)->where('status', true)->first();
                    if (!is_null($cartexist)) {
                        $totalquantity = $cartexist->quantity + $values["quantity"];
                        $totalprice = $totalquantity * $values["price"];
                        $cart = $cartexist->update([
                            'quantity' => $totalquantity,
                            'price' => $values["price"],
                            'total_price' => $totalprice,
                        ]);
                    } else {
                        $totalprice = $values["quantity"] * $values["price"];
                        $carts[] = EcommerceCart::firstOrCreate([
                            'user_id' => $userId,
                            'ecommerce_product_id' => $values["product_id"],
                            'quantity' => $values["quantity"],
                            'price' => $values["price"],
                            'total_price' => $totalprice,
                        ]);
                    }

                    $dataToLog = [
                        'causer_id' => auth()->user()->id,
                        'action_id' => $values["product_id"],
                        'action_type' => "Models\EcommerceCart",
                        'log_name' => "Items Moved to Cart successfully",
                        'action' => 'Create',
                        'description' => "Items Moved to Cart successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
                    ];

                    ProcessAuditLog::storeAuditLog($dataToLog);
                }

                DB::commit();

                $record = EcommerceCart::where('user_id', $userId)->where('status', true)->get();

                return JsonResponser::send(false, "Items Moved to Cart successfully!", $record, 200);
            }
            DB::rollBack();
            return JsonResponser::send(true, "Invalid carts format!", null, 400);
        } catch (\Throwable $error) {
            DB::rollback();
            return JsonResponser::send(true, $error->getMessage(), []);
        }
    }


      /**
     * Login and transfer item to cart
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function transferCartUpdate(Request $request)
    {
        $currentUserInstance = UserMgtHelper::userInstance();
        $userId = $currentUserInstance->id;

        try {
            DB::beginTransaction();
            if (is_array($request->carts)) {
                foreach ($request->carts as $key => $values) {
                    $cartexist = EcommerceCart::where('ecommerce_product_id', $values["product_id"])->where('user_id', $userId)->delete();
                    
                    $totalprice = $values["quantity"] * $values["price"];
                    $carts[] = EcommerceCart::firstOrCreate([
                        'user_id' => $userId,
                        'ecommerce_product_id' => $values["product_id"],
                        'quantity' => $values["quantity"],
                        'price' => $values["price"],
                        'total_price' => $totalprice,
                    ]);

                    $dataToLog = [
                        'causer_id' => $currentUserInstance->id,
                        'action_id' => $values["product_id"],
                        'action_type' => "Models\EcommerceCart",
                        'log_name' => "Items Moved to Cart successfully",
                        'action' => 'Create',
                        'description' => "Items Moved to Cart successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
                    ];

                    ProcessAuditLog::storeAuditLog($dataToLog);
                }

                DB::commit();

                $record = EcommerceCart::where('user_id', $userId)->where('status', true)->get();

                return JsonResponser::send(false, "Items Moved to Cart successfully!", $record, 200);
            }
            DB::rollBack();
            return JsonResponser::send(true, "Invalid carts format!", null, 400);
        } catch (\Throwable $error) {
            DB::rollback();
            return JsonResponser::send(true, $error->getMessage(), []);
        }
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $userInstance = UserMgtHelper::userInstance();
        $userId = $userInstance->id;

        try {

            DB::beginTransaction();

            $cart = EcommerceCart::where('id', $id)->where('user_id', $userId)->first();

            if (is_null($cart)) {
                return JsonResponser::send(true, "Item not found", [], 400);
            }

            if (!$cart->product->in_stock || $cart->product->available_quantity < 1) {
                return JsonResponser::send(true, "Product Out of stock!", null, 400);
            }

            if ($cart->product->available_quantity < $request->quantity) {
                return JsonResponser::send(true, "Can not add more than the available quantiy", null, 400);
            }

            $totalprice = $request->quantity * $cart->price;

            $cart->update([
                'size' => $request->size,
                'quantity' => $request->quantity,
                'total_price' => $totalprice,
            ]);

            $dataToLog = [
                'causer_id' => $userId,
                'action_id' => $cart->id,
                'action_type' => "Models\EcommerceCart",
                'log_name' => "Cart updated successfully",
                'action' => 'Update',
                'description' => "Cart updated successfully by {$userInstance->lastname} {$userInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            DB::commit();

            return JsonResponser::send(false, "Cart Updated Successfully", $cart, 200);
        } catch (\Throwable $error) {
            DB::rollback();
            return JsonResponser::send(true, $error->getMessage(), []);
        }
    }

     /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $userInstance = UserMgtHelper::userInstance();
        $userId = $userInstance->id;

        try {
            DB::beginTransaction();
            $cart = EcommerceCart::where('ecommerce_product_id', $id)->where('user_id', $userId)->first();

            if (is_null($cart)) {
                return JsonResponser::send(true, "Record not found", [], 400);
            }

            $currentUserInstance = auth()->user();

            $dataToLog = [
                'causer_id' => $userId,
                'action_id' => $cart->id,
                'action_type' => "Models\EcommerceCart",
                'log_name' => "Cart deleted successfully",
                'action' => 'Delete',
                'description' => "Cart deleted successfully by {$userInstance->lastname} {$userInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            $cart->delete();
            DB::commit();

            return JsonResponser::send(false, "Item has been removed from cart successfully", null, 200);
        } catch (\Throwable $error) {
            DB::rollback();
            return JsonResponser::send(true, $error->getMessage(), []);
        }
    }

    /**
     * Validate Cart Request
     */
    public function validateCart(Request $request)
    {
        $rules = [
            'product_id' => 'required',
            'price' => 'required',
            'quantity' => 'required',
            // 'color' => 'required',
            // 'size' => 'required',
        ];
        $validateCart = Validator::make($request->all(), $rules);
        return $validateCart;
    }

}