<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Category\CategoryController AS AdminCategoryController;
use SbscPackage\Ecommerce\Http\Controllers\v1\Admin\SubCategory\SubCategoryController AS AdminSubCategoryController;
use SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Product\ProductController AS AdminProductController;
use SbscPackage\Ecommerce\Http\Controllers\v1\Admin\ActivityLog\ActivityLogController AS AdminActivityLogController;

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
        });

        //Products
        Route::group(['prefix' => 'products'], function () {
            Route::post('/all', [AdminProductController::class, 'listAllProducts']);
            Route::get('/pending', [AdminProductController::class, 'listAllPendingProducts']);
            Route::get('/approved', [AdminProductController::class, 'listAllApprovedProducts']);
            Route::post('/export', [AdminProductController::class, 'exportProducts']);
            Route::put('/{id}/activate', [AdminProductController::class, 'activate']);
            Route::put('/{id}/deactivate', [AdminProductController::class, 'deactivate']);
            Route::get('/{id}', [AdminProductController::class, 'show']);
            Route::put('/update/{id}', [AdminProductController::class, 'update']);
            Route::post('/store', [AdminProductController::class, 'store']);
            Route::get('/stats/all', [AdminProductController::class, 'productStat']);
        });

    });

});
