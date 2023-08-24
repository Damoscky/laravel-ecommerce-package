<?php

namespace App\Http\Controllers\v1\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Helpers\ProcessAuditLog;
use App\Responser\JsonResponser;
use App\Http\Requests\CreateProductRequest;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductReportExport;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * fetch list of all products
     */
    public function listAllProducts(Request $request)
    {

        $productNameSearchParam = $request->product_name;
        $productDescriptionSearchParam = $request->product_description;
        $priceSearchParam = $request->price;
        $categorySearchParam = $request->category;
        $statusSearchParam = $request->status;

        (!is_null($request->product_start_date) && !is_null($request->product_end_date)) ? $dateSearchParams = true : $dateSearchParams = false;


        try {
            $products = Product::with('subcategory')->where("created_at", "!=", null)->orderBy('id', 'DESC')
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
                    return $query->where('is_active', $statusSearchParam);
                })->paginate(10);

            if (!$products) {
                return JsonResponser::send(true, "Record not found.", [], 400);
            }

            return JsonResponser::send(false, $products->count() . ' Product(s) Available', $products);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    /**
     * fetch list of all pending products
     */
    public function listAllPendingProducts()
    {
        try {
            $products = Product::where("status", "Pending")
                ->where("created_at", "!=", null)
                ->orderBy('id', 'DESC')->paginate(10);

            return JsonResponser::send(false, $products->count() . ' Product(s) Available', $products);
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
        try {
            $products = Product::where("status", "Approved")
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

        $currentUserInstance = \Session::get('user');
        $userId = $currentUserInstance['id'];

        try {
            DB::beginTransaction();

            $images = [];

            if (isset($request->product_images)) {
                $productImages = $request->product_images;

                $imageInfo = explode(';base64,', $productImages);
                $checkExtention = explode('data:', $imageInfo[0]);
                $checkExtention = explode('/', $checkExtention[1]);
                $fileExt = str_replace(' ', '', $checkExtention[1]);
                $image = str_replace(' ', '+', $imageInfo[1]);
                $uniqueId = bin2hex(openssl_random_pseudo_bytes(4));
                $name = 'invoice_' . $uniqueId . '_' . date("YmdHis") . '.' . $fileExt;
                $fileUrl = config('app.url') . 'products/' . $name;
                Storage::disk('products')->put($name, base64_decode($image));

                $images[] = $fileUrl;
            } else {
                $images[] = null;
            }

            $product = Product::create([
                'category_id'  => $request->category_id,
                'sub_category_id'  => $request->sub_category_id,
                'product_name' => $request->product_name,
                'long_description' => $request->long_description,
                'short_description' => $request->short_description,
                'tags' => $request->tags,
                'brand_name' => $request->brand_name,
                'sku' => $request->sku,
                'minimum_purchase_per_quantity' => $request->minimum_purchase_per_quantity,
                'quantity_supplied' => $request->quantity_supplied,
                'quantity_purchased' => $request->quantity_supplied,
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
                'product_images' =>  implode("|", $images),
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstance['id'],
                'action_id' => $product->id,
                'action_type' => "Models\Product",
                'log_name' => "Product created Successfully",
                'description' => "Product created Successfully by {$currentUserInstance['lastname']} {$currentUserInstance['firstname']}",
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
        $productNameSearchParam = $request->search;
        $productDescriptionSearchParam = $request->product_description;
        $priceSearchParam = $request->price;
        $categorySearchParam = $request->category;
        $statusSearchParam = $request->status;

        (!is_null($request->product_start_date) && !is_null($request->product_end_date)) ? $dateSearchParams = true : $dateSearchParams = false;


        try {
            $products = Product::orderBy('id', 'DESC')
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
        try {
            $product = Product::where('id', $id)->first();

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
        $product = Product::find($id);

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

        $currentUserInstance = \Session::get('user');
        $userId = $currentUserInstance['id'];


        try {
            DB::beginTransaction();

            $images = $product->product_images;

            if ($productImages = $request->product_images) {
                $imageInfo = explode(';base64,', $productImages);
                $checkExtention = explode('data:', $imageInfo[0]);
                $checkExtention = explode('/', $checkExtention[1]);
                $fileExt = str_replace(' ', '', $checkExtention[1]);
                $image = str_replace(' ', '+', $imageInfo[1]);
                $uniqueId = bin2hex(openssl_random_pseudo_bytes(4));
                $name = 'invoice_' . $uniqueId . '_' . date("YmdHis") . '.' . $fileExt;
                $fileUrl = config('app.url') . 'products/' . $name;
                Storage::disk('products')->put($name, base64_decode($image));
                $imagesArr[] = $fileUrl; 
                
                $images = implode('|', $imagesArr);
            } else {
                $images = $product->product_images;
            }


            $product->update([
                'category_id'  => $request->category_id,
                'sub_category_id'  => $request->sub_category_id,
                'product_name' => $request->product_name,
                'long_description' => $request->long_description,
                'short_description' => $request->short_description,
                'tags' => $request->tags,
                'brand_name' => $request->brand_name,
                'sku' => $request->sku,
                'minimum_purchase_per_quantity' => $request->minimum_purchase_per_quantity,
                'quantity_supplied' => $request->quantity_supplied,
                'quantity_purchased' => $request->quantity_supplied,
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
                'product_images' =>  $images,
                'status' => 'Pending'
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstance['id'],
                'action_id' => $product->id,
                'action_type' => "Models\Product",
                'log_name' => "Product updated Successfully",
                'description' => "Product updated Successfully by {$currentUserInstance['lastname']} {$currentUserInstance['firstname']}",
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

     /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                return JsonResponser::send(true, 'Product not found', [], 400);
            }

            $currentUserInstance = \Session::get('user');

            DB::beginTransaction();

            $dataToLog = [
                'causer_id' => $currentUserInstance['id'],
                'action_id' => $product->id,
                'action_type' => "Models\Product",
                'log_name' => "Product deleted Successfully",
                'description' => "Product deleted Successfully by {$currentUserInstance['lastname']} {$currentUserInstance['firstname']}",
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
        $product = Product::find($id);

        if (!$product) {
            return JsonResponser::send(true, 'Product not found', null);
        }

        try {
            DB::beginTransaction();

            $product->update([
                'is_active' => 1,
                'status' => 'Approved'
            ]);

            $currentUserInstance = \Session::get('user');

            $dataToLog = [
                'causer_id' => $currentUserInstance['id'],
                'action_id' => $product->id,
                'action_type' => "Models\Product",
                'log_name' => "Product approved Successfully",
                'description' => "Product activated Successfully by {$currentUserInstance['lastname']} {$currentUserInstance['firstname']}",
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
        $product = Product::find($id);
        if (!$product) {
            return JsonResponser::send(true, 'Product not found', [], 400);
        }

        try {
            DB::beginTransaction();

            $product->update([
                'is_active' => 0,
                'status' => "Declined"
            ]);

            $currentUserInstance = \Session::get('user');

            $dataToLog = [
                'causer_id' => $currentUserInstance['id'],
                'action_id' => $product->id,
                'action_type' => "Models\Product",
                'log_name' => "Product deactivated Successfully",
                'description' => "Product deactivated Successfully by {$currentUserInstance['lastname']} {$currentUserInstance['firstname']}",
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
            $products = Product::count();
            $allProducts = Product::all();
            $activatedProducts = Product::where('is_active', true)->get();
            $deactivatedProducts = Product::where('is_active', false)->get();
            $deactivatedProductsCount = Product::where('is_active', false)->count();
            $activatedProductsCount = Product::where('is_active', true)->count();
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
