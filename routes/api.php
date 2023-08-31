<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Category\CategoryController AS AdminCategoryController;
use SbscPackage\Ecommerce\Http\Controllers\v1\Admin\SubCategory\SubCategoryController AS AdminSubCategoryController;
use SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Product\ProductController AS AdminProductController;
use SbscPackage\Ecommerce\Http\Controllers\v1\Admin\ActivityLog\ActivityLogController AS AdminActivityLogController;
use SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Customer\CustomerController AS AdminCustomerController;
use SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Complaint\ComplaintController AS AdminComplaintController;
use SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Plan\PlanController AS AdminPlanController;
use SbscPackage\Ecommerce\Http\Controllers\v1\Vendor\RegisterController AS VendorRegisterController;
use SbscPackage\Ecommerce\Http\Controllers\v1\Customer\RegisterController AS CustomerRegisterController;
use SbscPackage\Ecommerce\Http\Controllers\v1\Customer\ProfileController AS CustomerProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(["prefix" => "v1/ecommerce"], function () {
    /** Cache */
    Route::get('/clear-cache', function () {
        Artisan::call('optimize:clear');
        return "Ecommerce Cache is cleared";
    });

    //Authentication Route
    Route::group(["prefix" => "vendor/auth"], function () {
        Route::post('register', [VendorRegisterController::class, 'register']);
    });


    // Vendor Route
    Route::group(['prefix' => 'customer', "namespace" => "v1\Customer", 'middleware' => ["auth:api", "ecommercecustomer"]], function () {
        Route::put('/profile/update', [CustomerProfileController::class, 'updateProfile']);
        Route::put('/billing/update', [CustomerProfileController::class, 'updateBillingInfo']);
        Route::put('/shipping/update', [CustomerProfileController::class, 'updateShippingInfo']);

    });

        // Vendor Route
    Route::group(['prefix' => 'vendor', "namespace" => "v1\Vendor", 'middleware' => ["auth:api", "ecommercevendor"]], function () {


    });
    // 'middleware' => ["core", "admin"]
    Route::group(['prefix' => 'admin', "namespace" => "v1\Admin", 'middleware' => ["auth:api", "ecommerceadmin"]], function () {


         /** Admin Category Route ***/
         Route::group(["prefix" => "category", "namespace" => "v1\Admin"], function () {
            Route::post('/', [AdminCategoryController::class, 'index']);
            Route::post('/store', [AdminCategoryController::class, 'store']);
            Route::put('/update/{id}', [AdminCategoryController::class, 'update']);
            Route::put('/{id}/activate', [AdminCategoryController::class, 'activate']);
            Route::put('/{id}/deactivate', [AdminCategoryController::class, 'deactivate']);
            Route::put('/pending/delete/{id}', [AdminCategoryController::class, 'deleteCategory']);
            Route::post('/export', [AdminCategoryController::class, 'exportCategories']);
            Route::post('/pending', [AdminCategoryController::class, 'pendingCategory']);
            Route::post('/pending/delete', [AdminCategoryController::class, 'pendingDeletedCategory']);
            Route::delete('/approve/delete/{id}', [AdminCategoryController::class, 'approveDeletedCategory']);
            Route::post('/approved', [AdminCategoryController::class, 'approvedCategory']);
            Route::get('/no-pagination', [AdminCategoryController::class, 'getCategoryNoPagination']);
        });

         /*** Admin Sub Category Route ***/
         Route::group(["prefix" => 'subcategory', "namespace" => "v1\Admin"], function () {

            Route::post('/', [AdminSubCategoryController::class, 'index']);
            Route::get('/no-pagination', [AdminSubCategoryController::class, 'getSubCategoryNoPagination']);
            Route::post('/store', [AdminSubCategoryController::class, 'store']);
            Route::put('/update/{id}', [AdminSubCategoryController::class, 'update']);
            Route::put('/delete/{id}', [AdminSubCategoryController::class, 'update']);
            Route::post('/pending', [AdminSubCategoryController::class, 'pendingSubCategory']);
            Route::put('/pending/delete/{id}', [AdminSubCategoryController::class, 'deleteSubCategory']);
            Route::delete('/approve/delete/{id}', [AdminSubCategoryController::class, 'approveDeletedSubcategory']);
            Route::post('/pending', [AdminSubCategoryController::class, 'pendingSubcategory']);
            Route::put('/{id}/activate', [AdminSubCategoryController::class, 'activate']);
            Route::put('/{id}/deactivate', [AdminSubCategoryController::class, 'deactivate']);
            Route::get('/no-pagination', [AdminSubCategoryController::class, 'getSubCategoryNoPagination']);
            Route::get('/by-category/{id}', [AdminSubCategoryController::class, 'getSubCategoryByCategoryId']);
        }); 

        //Products
        Route::group(['prefix' => 'products'], function () {
            Route::post('/all', [AdminProductController::class, 'listAllProducts']);
            Route::post('/request/all', [AdminProductController::class, 'listAllRequestProducts']);
            Route::post('/request/delete', [AdminProductController::class, 'listAllDeleteRequestProducts']);
            Route::post('/request/pending', [AdminProductController::class, 'listAllPendingRequestProducts']);
            Route::post('/activated', [AdminProductController::class, 'listAllActivatedProducts']);
            Route::post('/deactivated', [AdminProductController::class, 'listAllDeactivatedProducts']);
            Route::get('/approved', [AdminProductController::class, 'listAllApprovedProducts']);
            Route::post('/export', [AdminProductController::class, 'exportProducts']);
            Route::put('/{id}/activate', [AdminProductController::class, 'activate']);
            Route::put('/{id}/deactivate', [AdminProductController::class, 'deactivate']);
            Route::get('/{id}', [AdminProductController::class, 'show']);
            Route::put('/update/{id}', [AdminProductController::class, 'update']);
            Route::put('/update/delete/{id}', [AdminProductController::class, 'deleteForApproval']);
            Route::delete('/request/delete/{id}', [AdminProductController::class, 'approveDeletedProduct']);
            Route::put('/request/delete/decline/{id}', [AdminProductController::class, 'declineDeletedProduct']);
            Route::put('/approve/{id}', [AdminProductController::class, 'approvePendingProduct']);
            Route::put('/decline/{id}', [AdminProductController::class, 'declinePendingProduct']);
            Route::post('/store', [AdminProductController::class, 'store']);
            Route::get('/stats/all', [AdminProductController::class, 'productStat']);
        });

        Route::group(['prefix' => 'auditlogs'], function () {
            Route::post('/', [AdminActivityLogController::class, 'index']);
            Route::get('/{id}', [AdminActivityLogController::class, 'show']);

        });

        Route::group(['prefix' => 'customers'], function () {
            Route::get('/stats', [AdminCustomerController::class, 'customerStat']);
            Route::post('/', [AdminCustomerController::class, 'index']);
            Route::post('/active', [AdminCustomerController::class, 'activeCustomers']);
            Route::post('/inactive', [AdminCustomerController::class, 'inactiveCustomers']);
            Route::put('/update/status/{id}', [AdminCustomerController::class, 'updateCustomerStatus']);
            Route::get('/{id}', [AdminCustomerController::class, 'show']);

        });

        Route::group(['prefix' => 'complaints'], function () {
            Route::post('/all', [AdminComplaintController::class, 'listAllComplaints']);
            Route::post('/update/{id}', [AdminComplaintController::class, 'update']);
            Route::get('/stats', [AdminComplaintController::class, 'complaintsStat']);
            Route::get('/{id}', [AdminComplaintController::class, 'show']);

        });

        Route::group(['prefix' => 'plans'], function () {
            Route::post('/all', [AdminPlanController::class, 'listAllPlan']);
            Route::post('/create', [AdminPlanController::class, 'store']);
            Route::put('/update/{id}', [AdminPlanController::class, 'update']);
            Route::get('/{id}}', [AdminPlanController::class, 'show']);
        });

    });

});
