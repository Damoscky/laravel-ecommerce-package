<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\SubCategory;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades;
use Illuminate\Support\Carbon;
use DB;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Helpers\UserMgtHelper;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class VendorController extends BaseController
{
    public function index(Request $request)
    {
        if(!auth()->user()->hasPermission('view.vendor')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $vendorNameSearchParam = $request->vendor_name;
        $statusSearchParam = $request->status;
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->product_start_date) && !is_null($request->product_end_date)) ? $dateSearchParams = true : $dateSearchParams = false;


        try {
            $roleName = "ecommercevendor";
            $records = User::whereHas('roles', function ($roleTable) use ($roleName) {
                return $roleTable->where('slug', $roleName);
            })->when($sortByRequestParam, function ($query) use ($request) {
                if(isset($request->sort_by) && $request->sort_by == "alphabetically"){
                    return $query->orderBy('product_name', 'asc');
                }else if(isset($request->sort_by) && $request->sort_by == "date_old_to_new"){
                    return $query->orderBy('created_at', 'asc');
                }else if(isset($request->sort_by) && $request->sort_by == "date_new_to_old"){
                    return $query->orderBy('created_at', 'desc');
                }
            })->when($vendorNameSearchParam, function ($query, $vendorNameSearchParam) use ($request) {
                return $query->where('firstname', 'LIKE', '%' . $vendorNameSearchParam . '%');
            })->when($statusSearchParam, function ($query, $statusSearchParam) use ($request) {
                return $query->where('status', $statusSearchParam);
            });

            if(isset($request->export)){
                $records = $records->get();
                return Excel::download(new VendorReportExport($records), 'vendorreportdata.xlsx');
            }else{
                $records = $records->paginate(12);
                return JsonResponser::send(false, 'Record found successfully', $records, 200);
            }

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

}