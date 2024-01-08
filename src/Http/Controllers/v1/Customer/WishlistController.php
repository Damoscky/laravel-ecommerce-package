<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Customer;

use SbscPackage\Ecommerce\Models\EcommerceWishlist;
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

class WishlistController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $wishlists = EcommerceWishlist::where('user_id', auth()->user()->id)->with('ecommerceproduct')->paginate(10);
        // Check if the user is signed in and has the artwork in their wishlist
        return JsonResponser::send(false, $wishlists->count() . ' Item(s) Found', $wishlists, 200);

    }

     /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       try {
            /**
             * Validate Data
             */
            $validate = $this->validateWishlist($request);
            /**
             * if validation fails
             */
            if ($validate->fails()) {
                return JsonResponser::send(false, $validate->errors()->first(), $validate->errors()->all(), 400);
            }
            DB::beginTransaction();

            $currentUserInstance = UserMgtHelper::userInstance();
            $userId = $currentUserInstance->id;

            /**
             * if validate pass, save Wishlist
             */
            $checkwishlists = EcommerceWishlist::where('ecommerce_product_id', $request->product_id)->where('user_id', $userId)->first();
            if ($checkwishlists) {
                return JsonResponser::send(false, "Item Already in Wishlist", $checkwishlists, 200);

            }

            $totalprice = $request->quantity * $request->price;

            $wishlist = EcommerceWishlist::create([
                'user_id' => $userId,
                'ecommerce_product_id' => $request->product_id,
                'price' => $request->price,
                'total_price' => $totalprice,
            ]);

            $addedWishlist = EcommerceWishlist::where('ecommerce_product_id', $request->product_id)->where('user_id', auth()->user()->id)->first();

            $dataToLog = [
                'causer_id' => $userId,
                'action_id' => $wishlist->id,
                'action_type' => "Models\EcommerceWishlist",
                'log_name' => "Item added to Wishlist successfully",
                'action' => 'Create',
                'description' => "Item added to Wishlist successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            DB::commit();
            return JsonResponser::send(false, "Item added to Wishlist!", $addedWishlist, 201);
       } catch (\Throwable $error) {
            DB::rollBack();
			Log::error($error);
			return JsonResponser::send(true, $error->getMessage(), [], 500);
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
        try {
            $currentUserInstance = UserMgtHelper::userInstance();
            $userId = $currentUserInstance->id;

            DB::beginTransaction();
            $wishlist = EcommerceWishlist::where('ecommerce_product_id', $id)->where('user_id', $userId)->first();

            if (is_null($wishlist)) {
                return JsonResponser::send(true, "Wishlist not found", [], 404);
            }

            $dataToLog = [
                'causer_id' => $userId,
                'action_id' => $wishlist->id,
                'action_type' => "Models\EcommerceWishlist",
                'log_name' => "Item Removed from Wishlist successfully",
                'action' => 'Delete',
                'description' => "Item Removed from Wishlist successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            $wishlist->delete();

            DB::commit();
            return JsonResponser::send(false, "Item Removed from Wishlist!", [], 200);

        } catch (\Throwable $error) {
            DB::rollBack();
			Log::error($error);
			return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    /**
     * Validate Wishlist Request
     */
    public function validateWishlist($request)
    {
        $rules = [
            'product_id' => 'required',
            'price' => 'required',
            // 'size' => 'required',
            // 'color' => 'required',
            'quantity' => 'required',
        ];
        $validateWishlist = Validator::make($request->all(), $rules);
        return $validateWishlist;
    }

}