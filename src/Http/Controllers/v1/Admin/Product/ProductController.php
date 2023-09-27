<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Product;

use Illuminate\Routing\Controller as BaseController;
use SbscPackage\Ecommerce\Models\EcommerceProduct;
use Illuminate\Http\Request;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use SbscPackage\Ecommerce\Http\Requests\CreateProductRequest;
use Maatwebsite\Excel\Facades\Excel;
use SbscPackage\Ecommerce\Exports\ProductReportExport;
use SbscPackage\Ecommerce\Models\Category;
use Illuminate\Support\Facades\Validator;
use SbscPackage\Ecommerce\Services\Paystack;
use Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SbscPackage\Ecommerce\Helpers\UserMgtHelper;
use SbscPackage\Ecommerce\Interfaces\ProductStatusInterface;
use SbscPackage\Ecommerce\Helpers\FileUploadHelper;
use SbscPackage\Ecommerce\Models\EcommerceOrderDetails;
use SbscPackage\Ecommerce\Models\EcommerceVendor;

class ProductController extends BaseController
{
    /**
     * fetch list of all products
     */
    public function listAllProducts(Request $request)
    {
        if(!auth()->user()->hasPermission('view.ecommerceproducts')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $productNameSearchParam = $request->product_name;
        $productDescriptionSearchParam = $request->product_description;
        $priceSearchParam = $request->price;
        $categorySearchParam = $request->category_id;
        $statusSearchParam = $request->status;
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->product_start_date) && !is_null($request->product_end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        try {
            $products = EcommerceProduct::with('subcategory', 'category')->where("created_at", "!=", null)
                ->when($categorySearchParam, function ($query, $categorySearchParam) use ($request) {
                    return $query->whereHas('category', function ($query) use ($categorySearchParam) {
                        return $query->where('id',  $categorySearchParam);
                    });
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
                })->when($productNameSearchParam, function ($query, $productNameSearchParam) use ($request) {
                    return $query->where('product_name', 'LIKE', '%' . $productNameSearchParam . '%');
                })->when($productDescriptionSearchParam, function ($query, $productDescriptionSearchParam) use ($request) {
                    return $query->where('short_description', 'LIKE', '%' . $productDescriptionSearchParam . '%');
                })->when($priceSearchParam, function ($query, $priceSearchParam) use ($request) {
                    return $query->where('sales_price', $priceSearchParam);
                })->when($statusSearchParam, function ($query, $statusSearchParam) use ($request) {
                    return $query->where('status', $statusSearchParam);
                });

            if(isset($request->export)){
                $products = $products->get();
                return Excel::download(new ProductReportExport($products), 'productreportdata.xlsx');
            }else{
                $products = $products->paginate(12);
                return JsonResponser::send(false, 'Record found successfully', $products, 200);
            }

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    /**
     * fetch list of all Activated products
     */
    public function listAllActivatedProducts(Request $request)
    {
        if(!auth()->user()->hasPermission('view.ecommerceproducts')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $productNameSearchParam = $request->product_name;
        $productDescriptionSearchParam = $request->product_description;
        $priceSearchParam = $request->price;
        $categorySearchParam = $request->category_id;
        $statusSearchParam = $request->status;
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->product_start_date) && !is_null($request->product_end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        try {
            $products = EcommerceProduct::with('subcategory', 'category')->where("created_at", "!=", null)
                ->when($categorySearchParam, function ($query, $categorySearchParam) use ($request) {
                    return $query->whereHas('category', function ($query) use ($categorySearchParam) {
                        return $query->where('id',  $categorySearchParam);
                    });
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
                })->when($productNameSearchParam, function ($query, $productNameSearchParam) use ($request) {
                    return $query->where('product_name', 'LIKE', '%' . $productNameSearchParam . '%');
                })->when($productDescriptionSearchParam, function ($query, $productDescriptionSearchParam) use ($request) {
                    return $query->where('short_description', 'LIKE', '%' . $productDescriptionSearchParam . '%');
                })->when($priceSearchParam, function ($query, $priceSearchParam) use ($request) {
                    return $query->where('sales_price', $priceSearchParam);
                })->when($statusSearchParam, function ($query, $statusSearchParam) use ($request) {
                    return $query->where('status', $statusSearchParam);
                })->where('status', ProductStatusInterface::ACTIVE)->where('is_active', true);

            if(isset($request->export)){
                $products = $products->get();
                return Excel::download(new ProductReportExport($products), 'categoriesreportdata.xlsx');
            }else{
                $products = $products->paginate(12);
                return JsonResponser::send(false, 'Record found successfully', $products, 200);
            }

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }


    /**
     * fetch list of all Deactivated products
     */
    public function listAllDeactivatedProducts(Request $request)
    {
        if(!auth()->user()->hasPermission('view.ecommerceproducts')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $productNameSearchParam = $request->product_name;
        $productDescriptionSearchParam = $request->product_description;
        $priceSearchParam = $request->price;
        $categorySearchParam = $request->category_id;
        $statusSearchParam = $request->status;
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->product_start_date) && !is_null($request->product_end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        try {
            $products = EcommerceProduct::with('subcategory', 'category')->where("created_at", "!=", null)
                ->when($categorySearchParam, function ($query, $categorySearchParam) use ($request) {
                    return $query->whereHas('category', function ($query) use ($categorySearchParam) {
                        return $query->where('id',  $categorySearchParam);
                    });
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
                })->when($productNameSearchParam, function ($query, $productNameSearchParam) use ($request) {
                    return $query->where('product_name', 'LIKE', '%' . $productNameSearchParam . '%');
                })->when($productDescriptionSearchParam, function ($query, $productDescriptionSearchParam) use ($request) {
                    return $query->where('short_description', 'LIKE', '%' . $productDescriptionSearchParam . '%');
                })->when($priceSearchParam, function ($query, $priceSearchParam) use ($request) {
                    return $query->where('sales_price', $priceSearchParam);
                })->when($statusSearchParam, function ($query, $statusSearchParam) use ($request) {
                    return $query->where('status', $statusSearchParam);
                })->where('status', ProductStatusInterface::INACTIVE)->where('is_active', false);

            if(isset($request->export)){
                $products = $products->get();
                return Excel::download(new ProductReportExport($products), 'categoriesreportdata.xlsx');
            }else{
                $products = $products->paginate(12);
                return JsonResponser::send(false, 'Record found successfully', $products, 200);
            }

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    /**
     * fetch list of all Request products
     */
    public function listAllRequestProducts(Request $request)
    {
        if(!auth()->user()->hasPermission('manage.ecommerceproducts')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $productNameSearchParam = $request->product_name;
        $productDescriptionSearchParam = $request->product_description;
        $priceSearchParam = $request->price;
        $categorySearchParam = $request->category_id;
        $statusSearchParam = $request->status;
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->product_start_date) && !is_null($request->product_end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        try {
            $products = EcommerceProduct::with('subcategory', 'category')->where("created_at", "!=", null)
                ->when($categorySearchParam, function ($query, $categorySearchParam) use ($request) {
                    return $query->whereHas('category', function ($query) use ($categorySearchParam) {
                        return $query->where('id',  $categorySearchParam);
                    });
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
                })->when($productNameSearchParam, function ($query, $productNameSearchParam) use ($request) {
                    return $query->where('product_name', 'LIKE', '%' . $productNameSearchParam . '%');
                })->when($productDescriptionSearchParam, function ($query, $productDescriptionSearchParam) use ($request) {
                    return $query->where('short_description', 'LIKE', '%' . $productDescriptionSearchParam . '%');
                })->when($priceSearchParam, function ($query, $priceSearchParam) use ($request) {
                    return $query->where('sales_price', $priceSearchParam);
                })->when($statusSearchParam, function ($query, $statusSearchParam) use ($request) {
                    return $query->where('status', $statusSearchParam);
                })->where('status', ProductStatusInterface::PENDINGAPPROVAL)->orWhere('status', ProductStatusInterface::PENDINGDELETE);

            if(isset($request->export)){
                $products = $products->get();
                return Excel::download(new ProductReportExport($products), 'categoriesreportdata.xlsx');
            }else{
                $products = $products->paginate(12);
                return JsonResponser::send(false, 'Record found successfully', $products, 200);
            }

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    /**
     * fetch list of all Request products
     */
    public function listAllDeleteRequestProducts(Request $request)
    {
        if(!auth()->user()->hasPermission('manage.ecommerceproducts')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $productNameSearchParam = $request->product_name;
        $productDescriptionSearchParam = $request->product_description;
        $priceSearchParam = $request->price;
        $categorySearchParam = $request->category_id;
        $statusSearchParam = $request->status;
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->product_start_date) && !is_null($request->product_end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        try {
            $products = EcommerceProduct::with('subcategory', 'category')->where("created_at", "!=", null)
                ->when($categorySearchParam, function ($query, $categorySearchParam) use ($request) {
                    return $query->whereHas('category', function ($query) use ($categorySearchParam) {
                        return $query->where('id',  $categorySearchParam);
                    });
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
                })->when($productNameSearchParam, function ($query, $productNameSearchParam) use ($request) {
                    return $query->where('product_name', 'LIKE', '%' . $productNameSearchParam . '%');
                })->when($productDescriptionSearchParam, function ($query, $productDescriptionSearchParam) use ($request) {
                    return $query->where('short_description', 'LIKE', '%' . $productDescriptionSearchParam . '%');
                })->when($priceSearchParam, function ($query, $priceSearchParam) use ($request) {
                    return $query->where('sales_price', $priceSearchParam);
                })->when($statusSearchParam, function ($query, $statusSearchParam) use ($request) {
                    return $query->where('status', $statusSearchParam);
                })->where('status', ProductStatusInterface::PENDINGDELETE);

            if(isset($request->export)){
                $products = $products->get();
                return Excel::download(new ProductReportExport($products), 'categoriesreportdata.xlsx');
            }else{
                $products = $products->paginate(12);
                return JsonResponser::send(false, 'Record found successfully', $products, 200);
            }

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }
    /**
     * fetch list of all Request products
     */
    public function listAllPendingRequestProducts(Request $request)
    {
        if(!auth()->user()->hasPermission('manage.ecommerceproducts')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $productNameSearchParam = $request->product_name;
        $productDescriptionSearchParam = $request->product_description;
        $priceSearchParam = $request->price;
        $categorySearchParam = $request->category_id;
        $statusSearchParam = $request->status;
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->product_start_date) && !is_null($request->product_end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        try {
            $products = EcommerceProduct::with('subcategory', 'category')->where("created_at", "!=", null)
                ->when($categorySearchParam, function ($query, $categorySearchParam) use ($request) {
                    return $query->whereHas('category', function ($query) use ($categorySearchParam) {
                        return $query->where('id',  $categorySearchParam);
                    });
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
                })->when($productNameSearchParam, function ($query, $productNameSearchParam) use ($request) {
                    return $query->where('product_name', 'LIKE', '%' . $productNameSearchParam . '%');
                })->when($productDescriptionSearchParam, function ($query, $productDescriptionSearchParam) use ($request) {
                    return $query->where('short_description', 'LIKE', '%' . $productDescriptionSearchParam . '%');
                })->when($priceSearchParam, function ($query, $priceSearchParam) use ($request) {
                    return $query->where('sales_price', $priceSearchParam);
                })->when($statusSearchParam, function ($query, $statusSearchParam) use ($request) {
                    return $query->where('status', $statusSearchParam);
                })->where('status', ProductStatusInterface::PENDINGAPPROVAL);

            if(isset($request->export)){
                $products = $products->get();
                return Excel::download(new ProductReportExport($products), 'categoriesreportdata.xlsx');
            }else{
                $products = $products->paginate(12);
                return JsonResponser::send(false, 'Record found successfully', $products, 200);
            }

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    /**
     * fetch list of all active products
     */
    public function listAllApprovedProducts()
    {
        if(!auth()->user()->hasPermission('view.ecommerceproducts')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        try {
            $products = EcommerceProduct::where("status", "Approved")
                ->where("is_active", true)
                ->where("created_at", "!=", null)
                ->orderBy('id', 'DESC')->paginate(10);


            return JsonResponser::send(false, $products->count() . ' Product(s) Available', $products);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    /**
     * Store a single product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateProductRequest $request)
    {
        if(!auth()->user()->hasPermission('create.ecommerceproducts')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $currentUserInstance = UserMgtHelper::userInstance();
        $userId = $currentUserInstance->id;

        try {
            DB::beginTransaction();

            if (isset($request->product_image1)) {
                $productImage = $request->product_image1;
                $productKey = 'Product';
                $image1 = FileUploadHelper::singleStringFileUpload($productImage, $productKey);
            } else {
                $image1 = null;
            }

            if (isset($request->product_image2)) {
                $productImage = $request->product_image2;
                $productKey = 'Product';
                $image2 = FileUploadHelper::singleStringFileUpload($productImage, $productKey);
            } else {
                $image2 = null;
            }

            if (isset($request->product_image3)) {
                $productImage = $request->product_image3;
                $productKey = 'Product';
                $image3 = FileUploadHelper::singleStringFileUpload($productImage, $productKey);
            } else {
                $image3 = null;
            }

            if (isset($request->product_image4)) {
                $productImage = $request->product_image4;
                $productKey = 'Product';
                $image4 = FileUploadHelper::singleStringFileUpload($productImage, $productKey);
            } else {
                $image4 = null;
            }

            $vendorData = EcommerceVendor::where('business_name', "Fan")->first();
            isset($vendorData) ? $vendorId = $vendorData->id : $vendorId = 1;

            $paystackData = [
                'name' => $request->product_name,
                'description' => $request->short_description,
                'price' => $request->sales_price * 100,
                'currency' => 'NGN',
                'unlimited' => false,
                'quantity' => $request->quantity_supplied,
                'minimum_orderable' => $request->minimum_purchase_per_quantity
            ];

            $result = Paystack::createProduct($paystackData);
            if ($result["status"] !== true) {
                return JsonResponser::send(true, $result['message'], [], 400);
            }

            $product = EcommerceProduct::create([
                'category_id'  => $request->category_id,
                'sub_category_id'  => $request->sub_category_id,
                'ecommerce_vendor_id' =>$vendorId,
                'product_name' => $request->product_name,
                'long_description' => $request->long_description,
                'short_description' => $request->short_description,
                'product_code' => $result["data"]["product_code"],
                'tags' => $request->tags,
                'brand_name' => $request->brand_name,
                'manage_stock_quantity' => $request->manage_stock_quantity,
                'sku' => $request->sku,
                'minimum_purchase_per_quantity' => $request->minimum_purchase_per_quantity,
                'quantity_supplied' => $request->quantity_supplied,
                'quantity_purchased' => 0,
                'available_quantity' => $request->quantity_supplied,
                'regular_price' => $request->regular_price,
                'sales_price' => $request->sales_price,
                'shipping_fee' => $request->shipping_fee,
                'weight_type' => $request->weight_type,
                'weight' => $request->weight,
                'length' => $request->length,
                'width' => $request->width,
                'height' => $request->height,
                'ean' => $request->ean,
                'is_active' => false,
                'product_image1' =>  $image1,
                'product_image2' =>  $image2,
                'product_image3' =>  $image3,
                'product_image4' =>  $image4,
                'status' => ProductStatusInterface::PENDINGAPPROVAL,
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $product->id,
                'action_type' => "Models\Product",
                'log_name' => "Product created Successfully",
                'action' => 'Create',
                'description' => "Product created Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Product created Successfully!', $product);
        } catch (\Exception $th) {
            DB::rollBack();
            logger($th);
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }

    public function exportProducts(Request $request)
    {
        if(!auth()->user()->hasPermission('export.reports')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        $productNameSearchParam = $request->search;
        $productDescriptionSearchParam = $request->product_description;
        $priceSearchParam = $request->price;
        $categorySearchParam = $request->category;
        $statusSearchParam = $request->status;

        (!is_null($request->product_start_date) && !is_null($request->product_end_date)) ? $dateSearchParams = true : $dateSearchParams = false;


        try {
            $products = EcommerceProduct::orderBy('id', 'DESC')
                ->when($categorySearchParam, function ($query, $categorySearchParam) use ($request) {
                    return $query->whereHas('category', function ($query) use ($categorySearchParam) {
                        return $query->where('name', 'LIKE', '%' . $categorySearchParam . '%');
                    });
                })->when($productNameSearchParam, function ($query, $productNameSearchParam) use ($request) {
                    return $query->where('product_name', 'LIKE', '%' . $productNameSearchParam . '%');
                })->when($productDescriptionSearchParam, function ($query, $productDescriptionSearchParam) use ($request) {
                    return $query->where('short_description', 'LIKE', '%' . $productDescriptionSearchParam . '%');
                })->when($priceSearchParam, function ($query, $priceSearchParam) use ($request) {
                    return $query->where('sales_price', $priceSearchParam);
                })->when($statusSearchParam, function ($query, $statusSearchParam) use ($request) {
                    return $query->where('status', $statusSearchParam);
                })->paginate(10);

            return Excel::download(new ProductReportExport($products), 'productreportdata.xlsx');

            return JsonResponser::send(false, $products->count() . ' Product(s) Available', $products);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

     /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if(!auth()->user()->hasPermission('view.ecommerceproducts')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        try {
            $product = EcommerceProduct::where('id', $id)->first();

            if (!$product) {
                return JsonResponser::send(true, "Record not found.", [], 400);
            }

            return JsonResponser::send(false, 'Record found successfully', $product);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if(!auth()->user()->hasPermission('edit.ecommerceproducts')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        $product = EcommerceProduct::find($id);

        if (!$product) {
            return JsonResponser::send(true, 'Product Not Found', []);
        }

        /**
         * Validate Request
         */
        $validate = $this->validateProduct($request);
        /**
         * if validation fails
         */
        if ($validate->fails()) {
            return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all(), 400);
        }

        $currentUserInstance = UserMgtHelper::userInstance();
        $userId = $currentUserInstance->id;


        try {
            DB::beginTransaction();

            if (isset($request->product_image1)) {
                $productImage = $request->product_image1;
                $productKey = 'Product';
                $image1 = FileUploadHelper::singleStringFileUpload($productImage, $productKey);
            } else {
                $image1 = $product->product_image1;
            }

            if ($request->product_image2 == "no-image") {
                $image2 = "";
            } else if(isset($request->product_image2)){
                $productImage = $request->product_image2;
                $productKey = 'Product';
                $image2 = FileUploadHelper::singleStringFileUpload($productImage, $productKey);
            }else{
                $image2 = $product->product_image2;
            }

            if ($request->product_image3 == "no-image") {
                $image3 = "";
            }else if (isset($request->product_image3)) {
                $productImage = $request->product_image3;
                $productKey = 'Product';
                $image3 = FileUploadHelper::singleStringFileUpload($productImage, $productKey);
            } else {
                $image3 = $product->product_image3;
            }

            if ($request->product_image4 == "no-image") {
                $image4 = "";
            }else if (isset($request->product_image4)) {
                $productImage = $request->product_image4;
                $productKey = 'Product';
                $image4 = FileUploadHelper::singleStringFileUpload($productImage, $productKey);
            } else {
                $image4 = $product->product_image4;
            }

            $product->update([
                'category_id'  => $request->category_id,
                'sub_category_id'  => $request->sub_category_id,
                'product_name' => $request->product_name,
                'long_description' => $request->long_description,
                'short_description' => $request->short_description,
                'tags' => $request->tags,
                'brand_name' => $request->brand_name,
                'manage_stock_quantity' => $request->manage_stock_quantity,
                'sku' => $request->sku,
                'minimum_purchase_per_quantity' => $request->minimum_purchase_per_quantity,
                'quantity_supplied' => $request->quantity_supplied,
                'quantity_purchased' => $product->quantity_supplied,
                'available_quantity' => $request->quantity_supplied,
                'regular_price' => $request->regular_price,
                'sales_price' => $request->sales_price,
                'shipping_fee' => $request->shipping_fee,
                'weight_type' => $request->weight_type,
                'weight' => $request->weight,
                'length' => $request->length,
                'width' => $request->width,
                'height' => $request->height,
                'ean' => $request->ean,
                'product_image1' =>  $image1,
                'product_image2' =>  $image2,
                'product_image3' =>  $image3,
                'product_image4' =>  $image4,
                'in_stock' => 1, 
                'is_active' => false,
                'status' => ProductStatusInterface::PENDINGAPPROVAL,
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $product->id,
                'action_type' => "Models\Product",
                'log_name' => "Product updated Successfully",
                'action' => 'Edit',
                'description' => "Product updated Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Product Updated Successfully!', $product);
        } catch (\Throwable $error) {
            logger($error);
            DB::rollBack();
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }


    /**
     * validation
     */
    public function validateProduct(Request $request)
    {
        $rules = [
            'category_id' => 'required|integer|gt:0',
            'sub_category_id' => 'required|integer|gt:0',
            'product_name' => 'required|string|min:3|max:250',
            'long_description' => 'sometimes|min:5',
            'short_description' => 'sometimes|min:5',
            'quantity_supplied' => 'required|integer|gt:0',
            'minimum_purchase_per_quantity' => 'sometimes',
            "brand_name" => "sometimes|nullable|max:250",
            "regular_price" => "required",
            "sales_price" => "required",
            "product_material" => "string|nullable|max:250",
        ];
        if ($request->isMethod('put')) {
            $rules["product_images"] = "array";
        }

        $validateProduct = Validator::make($request->all(), $rules);
        return $validateProduct;
    }

    public function deleteForApproval($id)
    {
        if(!auth()->user()->hasPermission('edit.ecommerceproducts')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        try {
            $product = EcommerceProduct::find($id);
            if (!$product) {
                return JsonResponser::send(true, 'Product not found', [], 400);
            }

            $currentUserInstance = UserMgtHelper::userInstance();

            DB::beginTransaction();

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $product->id,
                'action_type' => "Models\Product",
                'log_name' => "Product delete sent for approval Successfully",
                'action' => 'Delete',
                'description' => "Product delete sent for approval Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            // delete old product images
            //$this->deleteFile($product->product_image);

            $product->update([
                'status' => ProductStatusInterface::PENDINGDELETE
            ]);
            DB::commit();
            return JsonResponser::send(false, 'Product deleted successfully', null);
        } catch (\Throwable $error) {
            logger($error);
            DB::rollBack();
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function approvePendingProduct($id)
    {
        if(!auth()->user()->hasPermission('manage.ecommerceproducts')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        $record = EcommerceProduct::find($id);

        if (!$record) {
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        try {
            $currentUserInstance = UserMgtHelper::userInstance();

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $record->id,
                'action_type' => "Models\EcommerceProduct",
                'log_name' => "Product Approved Successfully",
                'action' => 'Manage',
                'description' => "Product approved Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            $record->update([
                'status' => ProductStatusInterface::ACTIVE,
                'is_active' => true,
            ]);

            return JsonResponser::send(false, 'Product activated Successfully!', $record);
        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, 'Internal server error', null, 500);
        }
    }

    public function declinePendingProduct($id)
    {
        if(!auth()->user()->hasPermission('manage.ecommerceproducts')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        $record = EcommerceProduct::find($id);

        if (!$record) {
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        try {
            $currentUserInstance = UserMgtHelper::userInstance();

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $record->id,
                'action_type' => "Models\EcommerceProduct",
                'log_name' => "Product Declined Successfully",
                'action' => 'Manage',
                'description' => "Product declined Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            $record->update([
                'status' => ProductStatusInterface::DECLINED,
                'is_active' => false,
            ]);

            return JsonResponser::send(false, 'Product declined Successfully!', $record);
        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, 'Internal server error', null, 500);
        }
    }

    public function declineDeletedProduct($id)
    {
        if(!auth()->user()->hasPermission('manage.ecommerceproducts')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        $record = EcommerceProduct::find($id);

        if (!$record) {
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        try {
            $currentUserInstance = UserMgtHelper::userInstance();

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $record->id,
                'action_type' => "Models\EcommerceProduct",
                'log_name' => "Product Declined Successfully",
                'action' => 'Manage',
                'description' => "Product declined Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            $record->update([
                'status' => ProductStatusInterface::ACTIVE,
                'is_active' => true,
            ]);

            return JsonResponser::send(false, 'Product declined Successfully!', $record);
        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, 'Internal server error', null, 500);
        }
    }

    public function approveDeletedProduct($id)
    {
        if(!auth()->user()->hasPermission('delete.ecommerceproducts')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        $record = EcommerceProduct::find($id);

        if (!$record) {
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        try {

            $currentUserInstance = UserMgtHelper::userInstance();

            //check if product is already ordered
            $orederDetails = EcommerceOrderDetails::where('ecommerce_product_id', $id)->get();
            if(count($orederDetails) > 0){
                return JsonResponser::send(true, 'Unable to delete record. Product attached to an ordered', null, 400);
            }

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $record->id,
                'action_type' => "Models\EcommerceProduct",
                'log_name' => "Product deleted Successfully",
                'action' => 'Delete',
                'description' => "Product deleted Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            $record->delete();

            return JsonResponser::send(false, 'Product Deleted Successfully!', $record);
        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, 'Internal server error', null, 500);
        }
    }

     /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(!auth()->user()->hasPermission('delete.ecommerceproducts')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        try {
            $product = EcommerceProduct::find($id);
            if (!$product) {
                return JsonResponser::send(true, 'Product not found', [], 400);
            }

            $currentUserInstance = UserMgtHelper::userInstance();

            DB::beginTransaction();

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $product->id,
                'action_type' => "Models\Product",
                'log_name' => "Product deleted Successfully",
                'action' => 'Delete',
                'description' => "Product deleted Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            // delete old product images
            //$this->deleteFile($product->product_image);

            $product->delete();
            DB::commit();
            return JsonResponser::send(false, 'Product deleted successfully', null);
        } catch (\Throwable $error) {
            logger($error);
            DB::rollBack();
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    /**
     * Activate Product
     */

    public function activate($id)
    {
        if(!auth()->user()->hasPermission('manage.ecommerceproducts')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        $product = EcommerceProduct::find($id);

        if (!$product) {
            return JsonResponser::send(true, 'Product not found', null);
        }

        try {
            DB::beginTransaction();

            $product->update([
                'is_active' => 1,
                'status' => 'Approved'
            ]);

            $currentUserInstance = UserMgtHelper::userInstance();

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $product->id,
                'action_type' => "Models\Product",
                'log_name' => "Product approved Successfully",
                'action' => 'Manage',
                'description' => "Product activated Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Product Activated Successfully!', $product);
        } catch (\Throwable $th) {
            logger($th);
            DB::rollBack();
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }

    /**
     * Deactivate Product
     */
    public function deactivate($id)
    {
        if(!auth()->user()->hasPermission('manage.ecommerceproducts')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $product = EcommerceProduct::find($id);
        if (!$product) {
            return JsonResponser::send(true, 'Product not found', [], 400);
        }

        try {
            DB::beginTransaction();

            $product->update([
                'is_active' => 0,
                'status' => "Declined"
            ]);

            $currentUserInstance = UserMgtHelper::userInstance();

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $product->id,
                'action_type' => "Models\Product",
                'log_name' => "Product deactivated Successfully",
                'action' => 'Manage',
                'description' => "Product deactivated Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Product Deactivated Successfully!', $product);
        } catch (\Throwable $th) {
            logger($th);
            DB::rollBack();
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }

    public function productStat()
    {
        try {
            $products = EcommerceProduct::count();
            $allProducts = EcommerceProduct::all();
            $activatedProducts = EcommerceProduct::where('is_active', true)->get();
            $deactivatedProducts = EcommerceProduct::where('is_active', false)->get();
            $deactivatedProductsCount = EcommerceProduct::where('is_active', false)->where('status', ProductStatusInterface::INACTIVE)->count();
            $activatedProductsCount = EcommerceProduct::where('is_active', true)->where('status', ProductStatusInterface::ACTIVE)->count();
            $categories = Category::count();

            $data = [
                'totalProductsCount' => $products,
                'deactivatedProducts' => $deactivatedProducts,
                'activatedProducts' => $activatedProducts,
                'deactivatedProductsCount' => $deactivatedProductsCount,
                'activatedProductsCount' => $activatedProductsCount,
                'allProducts' => $allProducts,
                'categories' => $categories,
            ];

            return JsonResponser::send(false, 'Product Data', $data, 200);
        } catch (\Throwable $th) {
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }
}
