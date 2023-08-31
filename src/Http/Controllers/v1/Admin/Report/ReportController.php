<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Report;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades;
use Illuminate\Support\Carbon;
use DB;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Helpers\UserMgtHelper;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use Illuminate\Support\Facades\Validator;
use SbscPackage\Ecommerce\Exports\SubCategoriesReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use SbscPackage\Ecommerce\Helpers\FileUploadHelper;
use SbscPackage\Ecommerce\Models\EcommerceProduct;
use Carbon\Carbon;

class SubCategoryController extends BaseController
{

    public function index(Request $request)
    {
        try {
            $recordSearchParam = $request->searchByDate;

            if ($recordSearchParam == "1day") {
                $carbonDate = Carbon::now()->subdays(1);
            } elseif ($recordSearchParam == "7days") {
                $carbonDate = Carbon::now()->subdays(7);
            } elseif ($recordSearchParam == "30days") {
                $carbonDate = Carbon::now()->subdays(30);
            } elseif ($recordSearchParam == "3months") {
                $carbonDate = Carbon::now()->subMonth(3);
            } elseif ($recordSearchParam == "12months") {
                $carbonDate = Carbon::now()->subMonth(12);
            }elseif($recordSearchParam == "Custom Date"){
                (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;
            } else {
                $carbonDate = false;
            }

            $months = range(1, 12);
            $data = [
                "current_year" => [],
                "selected_year" => []
            ];

            foreach ($months as $month) {
                $presentYearSum = EcommerceProduct::whereMonth('created_at', $month)
                    // ->whereHas('')
                    ->whereYear('created_at', Carbon::now()->year)
                    ->when($store_id, function ($query) use ($store_id) {
                        return $query->where('store_id', $store_id);
                    })
                    ->sum('total_price');

                $selectedYearSum = EcommerceProduct::whereMonth('created_at', $month)
                    ->whereYear('created_at', $selectedYear)
                    ->when($store_id, function ($query) use ($store_id) {
                        return $query->where('store_id', $store_id);
                    })
                    ->sum('total_price');

                $data["current_year"][date('F', mktime(0, 0, 0, $month, 10))] = round($presentYearSum);
                $data["selected_year"][date('F', mktime(0, 0, 0, $month, 10))] = round($selectedYearSum);
            }

            return JsonResponser::send(false, 'Record(s) found successfully', $data, 200);
        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }

}