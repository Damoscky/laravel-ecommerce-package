<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Guest;

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
use SbscPackage\Ecommerce\Models\EcommerceOrderDetails;
use SbscPackage\Ecommerce\Models\EcommerceProduct;
use SbscPackage\Ecommerce\Models\SubCategory;
use Validator, Hash, DB;

class ProductController extends BaseController
{

    public function getProductsByCategories()
    {
        $records = Category::with(['ecommerceproduct' => function ($query) {
            $query->where('is_active', true)->take(4);
        }])->where('is_active', true)->paginate(10);
        return JsonResponser::send(false, "Record found successfully", $records, 200);
    }

    public function show($id)
    {
        $record = EcommerceProduct::find($id);

        if(is_null($record)){
            return JsonResponser::send(true, "Record not found", [], 400);

        }
        return JsonResponser::send(false, "Record found successfully", $record, 200);
    }

    public function getProductsBySubCategories()
    {
        $records = SubCategory::with(['ecommerceproduct' => function ($query) {
            $query->where('is_active', true)->take(4);
        }])->where('is_active', true)->take(3);
        return JsonResponser::send(false, "Record found successfully", $records, 200);
    }

    public function getProductsByCategoryId(Request $request, $id)
    {
        $subcatgorySearchParam = $request->sub_category_id;
        $sortByRequestParam = $request->sort_by;
        
        (!is_null($request->min_price) && !is_null($request->max_price)) ? $priceRangeSearchParams = true : $priceRangeSearchParams = false;

        try {
            $records = EcommerceProduct::when($subcatgorySearchParam, function($query) use($subcatgorySearchParam){
                return $query->where('sub_category_id', $subcatgorySearchParam);
            })->when($priceRangeSearchParams, function($query, $priceRangeSearchParams) use($request) {
                $minPrice = $request->min_price;
                $maxPrice = $request->max_price;
                return $query->whereBetween('sales_price', [$minPrice, $maxPrice]);
            })->when($sortByRequestParam, function ($query) use ($request) {
                if(isset($request->sort_by) && $request->sort_by == "alphabetically"){
                    return $query->orderBy('product_name', 'asc');
                }else if(isset($request->sort_by) && $request->sort_by == "date_old_to_new"){
                    return $query->orderBy('created_at', 'asc');
                }else if(isset($request->sort_by) && $request->sort_by == "date_new_to_old"){
                    return $query->orderBy('created_at', 'desc');
                }else{
                    return $query->orderBy('created_at', 'desc');
                }
            })->where('is_active', true)->where('category_id', $id)->paginate(10);
            return JsonResponser::send(false, "Record found successfully", $records, 200);

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function getAllCategoriesNoPagination()
    {
        $records = Category::withCount('ecommerceproduct')->where('is_active', true)->get();
        return JsonResponser::send(false, "Record found successfully", $records, 200);
    }

    public function getAllFeaturedProducts()
    {
        try {
            $records = EcommerceProduct::where('is_active', true)->where('featured', true)->take(4)->orderBy('created_at', 'DESC')->get();
            return JsonResponser::send(false, "Record found successfully", $records, 200);

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function getAllLatestProducts()
    {
        try {
            $records = EcommerceProduct::where('is_active', true)->take(4)->orderBy('created_at', 'DESC')->get();
            return JsonResponser::send(false, "Record found successfully", $records, 200);

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }        
    }

    public function getAllBestsellingProducts()
    {
        try {
            $bestsellerproducts = EcommerceOrderDetails::select('ecommerce_product_id')->groupBy('ecommerce_product_id')->orderByRaw('COUNT(*) DESC')->with('ecommerceproduct')->take(4)->get();
            return JsonResponser::send(false, "Record found successfully", $bestsellerproducts, 200);

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }        
    }

}