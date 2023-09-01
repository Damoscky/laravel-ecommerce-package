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
use SbscPackage\Ecommerce\Models\EcommerceProduct;
use Validator, Hash, DB;

class ProductController extends BaseController
{

    public function getProductsByCategories()
    {
        $categories = Category::where('is_active', 1)->get();
        collect($categories)->map(function ($producs){
            return $products = EcommerceProduct::whereIn('category_id', $categories);
        });

        // if(count($categories) == 0 ){
        //     return JsonResponser::send(true, "Record not found", [], 400);
        // }

        // foreach ($categories as $category) {
        //     # code...
        // }

        // $products = EcommerceProduct::
    }

}