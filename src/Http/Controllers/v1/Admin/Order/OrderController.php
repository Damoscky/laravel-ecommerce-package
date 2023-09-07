<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Order;

use Illuminate\Routing\Controller as BaseController;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use SbscPackage\Ecommerce\Exports\AuditLogsReportExport;
use Maatwebsite\Excel\Facades\Excel;
use SbscPackage\Ecommerce\Interfaces\ProductStatusInterface;
use SbscPackage\Ecommerce\Models\EcommerceOrderDetails;
use SbscPackage\Ecommerce\Models\EcommerceProduct;
use App\Models\User;
use SbscPackage\Ecommerce\Interfaces\OrderStatusInterface;
use SbscPackage\Ecommerce\Models\EcommerceOrder;

class OrderController extends BaseController
{

    public function index(Request $request)
    {
        if(!auth()->user()->hasPermission('view.ecommerceorders')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $recordSearchParam = $request->searchByDate;

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;
        $sortByRequestParam = $request->sort_by;
        $nameSearchParam = $request->customer_name;
        $statusSearchParam = $request->status;

        if ($recordSearchParam == "1day") {
            $carbonDateFilter = Carbon::now()->subdays(1);
        } elseif ($recordSearchParam == "7days") {
            $carbonDateFilter = Carbon::now()->subdays(7);
        } elseif ($recordSearchParam == "30days") {
            $carbonDateFilter = Carbon::now()->subdays(30);
        } elseif ($recordSearchParam == "3months") {
            $carbonDateFilter = Carbon::now()->subMonth(3);
        } elseif ($recordSearchParam == "12months") {
            $carbonDateFilter = Carbon::now()->subMonth(12);
        } elseif ($recordSearchParam == "24hours") {
            $carbonDateFilter = Carbon::now()->subDay(1);
        } else {
            $carbonDateFilter = false;
        }

        try {
            $pendingOrder = EcommerceOrder::where('status', OrderStatusInterface::PENDING)->count();
            $completedOrder = EcommerceOrder::where('status', OrderStatusInterface::DELIVERED)->count();
            $processingOrder = EcommerceOrder::where('status', OrderStatusInterface::PROCESSING)->count();
            $cancelledOrder = EcommerceOrder::where('status', OrderStatusInterface::CANCELLED)->count();


            $orders = EcommerceOrder::when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
                return $query->where('created_at', '>=', $carbonDateFilter);
            })->when($nameSearchParam, function ($query) use ($nameSearchParam) {
                    return $query->where('fullname', 'LIKE' , '%'. $nameSearchParam. '%');
            })->when($statusSearchParam, function ($query) use ($statusSearchParam) {
                return $query->where('status',  $statusSearchParam);
            })->when($sortByRequestParam, function ($query) use ($request) {
                if(isset($request->sort_by) && $request->sort_by == "alphabetically"){
                    return $query->orderBy('orderNO', 'asc');
                }else if(isset($request->sort_by) && $request->sort_by == "date_old_to_new"){
                    return $query->orderBy('created_at', 'asc');
                }else if(isset($request->sort_by) && $request->sort_by == "date_new_to_old"){
                    return $query->orderBy('created_at', 'desc');
                }else if(isset($request->sort_by) && $request->sort_by == "price_high_to_low"){
                    return $query->orderBy('total_price', 'desc');
                }else if(isset($request->sort_by) && $request->sort_by == "price_low_to_high"){
                    return $query->orderBy('total_price', 'asc');
                }else{
                    return $query->orderBy('created_at', 'desc');
                }
            })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
                $startDate = Carbon::parse($request->start_date);
                $endDate = Carbon::parse($request->end_date);
                return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
            })->paginate(10);

            $data = [
                'orders' => $orders,
                'pendingOrder' => $pendingOrder,
                'completedOrder' => $completedOrder,
                'processingOrder' => $processingOrder,
                'cancelledOrder' => $cancelledOrder,
            ];

            return JsonResponser::send(false, 'Record(s) found successfully', $data, 200);

        } catch (\Throwable $error) {
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }


}