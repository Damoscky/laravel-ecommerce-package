<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Report;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades;
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
use SbscPackage\Ecommerce\Interfaces\ProductStatusInterface;
use SbscPackage\Ecommerce\Exports\OrderExportReport;
use SbscPackage\Ecommerce\Exports\ProductReportExport;
use SbscPackage\Ecommerce\Models\EcommerceOrderDetails;
use SbscPackage\Ecommerce\Exports\CustomerReportExport;
use SbscPackage\Ecommerce\Models\EcommerceProduct;
use App\Models\User;
use SbscPackage\Ecommerce\Interfaces\OrderStatusInterface;
use SbscPackage\Ecommerce\Models\EcommerceOrder;
use Carbon\Carbon;

class ReportController extends BaseController
{

    public function products(Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('view.ecommercereports')) {
                return JsonResponser::send(true, "Permission Denied :(", [], 401);
            }

            $recordSearchParam = $request->searchByDate;

            (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;
            $sortByRequestParam = $request->sort_by;
            $nameSearchParam = $request->product_name;
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

            $products = EcommerceProduct::with('ecommerceVendor')->when($nameSearchParam, function ($query) use ($nameSearchParam) {
                return $query->where('product_name', 'LIKE', '%' . $nameSearchParam . '%');
            })->when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
                return $query->where('created_at', '>=', $carbonDateFilter);
            })->when($dateSearchParams, function ($query, $dateSearchParams) use ($request) {
                $startDate = Carbon::parse($request->start_date);
                $endDate = Carbon::parse($request->end_date);
                return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
            })->when($statusSearchParam, function ($query) use ($statusSearchParam) {
                return $query->where('status', $statusSearchParam);
            });

            $totalSales = EcommerceOrderDetails::whereHas('ecommerceproduct')
                ->when($dateSearchParams, function ($query, $dateSearchParams) use ($request) {
                    $startDate = Carbon::parse($request->start_date);
                    $endDate = Carbon::parse($request->end_date);
                    return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                })->when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
                    return $query->where('created_at', '>=', $carbonDateFilter);
                })->when($statusSearchParam, function ($query) use ($statusSearchParam) {
                    return $query->where('status', $statusSearchParam);
                })->selectRaw('SUM(unit_price * quantity_ordered) as total_sales')
                ->value('total_sales');

            $productSold = EcommerceProduct::whereHas('ecommerceorderdetails')
                ->when($dateSearchParams, function ($query, $dateSearchParams) use ($request) {
                    $startDate = Carbon::parse($request->start_date);
                    $endDate = Carbon::parse($request->end_date);
                    return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                })->when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
                    return $query->where('created_at', '>=', $carbonDateFilter);
                })->when($statusSearchParam, function ($query) use ($statusSearchParam) {
                    return $query->where('status', $statusSearchParam);
                })->count();

            $productsCreated = EcommerceProduct::whereHas('ecommerceorderdetails')
                ->when($dateSearchParams, function ($query, $dateSearchParams) use ($request) {
                    $startDate = Carbon::parse($request->start_date);
                    $endDate = Carbon::parse($request->end_date);
                    return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                })->when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
                    return $query->where('created_at', '>=', $carbonDateFilter);
                })->when($statusSearchParam, function ($query) use ($statusSearchParam) {
                    return $query->where('status', $statusSearchParam);
                })->count();

            if (isset($request->export)) {
                $products = $products->get();
                return Excel::download(new ProductReportExport($products), 'productreportdata.xlsx');
            } else {
                $products = $products->paginate(12);
            }

            $months = range(1, 12);
            $productChart = [];
            foreach ($months as $month) {
                $productSalesStat = EcommerceOrderDetails::whereHas('ecommerceproduct')
                    ->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', $month)
                    ->selectRaw('SUM(unit_price * quantity_ordered) as total_sales')
                    ->value('total_sales');

                $productCreatedStat = EcommerceProduct::whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', $month)
                    ->count();

                $productSoldStat = EcommerceProduct::whereHas('ecommerceorderdetails')
                    ->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', $month)
                    ->count();

                $productChart[date('F', mktime(0, 0, 0, $month, 10))] = ["productCreated" => $productCreatedStat, 'productSold' => $productSoldStat];
            }

            $data = [
                'productSold' => $productSold,
                'totalSales' => $totalSales,
                'productsCreated' => $productsCreated,
                'products' => $products,
                'productChart' => $productChart
            ];


            return JsonResponser::send(false, 'Record(s) found successfully', $data, 200);
        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }


    public function orders(Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('view.ecommercereports')) {
                return JsonResponser::send(true, "Permission Denied :(", [], 401);
            }

            $recordSearchParam = $request->searchByDate;
            $statusSearchParam = $request->status;

            (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;
            $sortByRequestParam = $request->sort_by;
            $nameSearchParam = $request->order_no;
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

            $totalOrderRevenue = EcommerceOrder::when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
                return $query->where('created_at', '>=', $carbonDateFilter);
            })->when($statusSearchParam, function ($query) use ($statusSearchParam) {
                return $query->where('status',  $statusSearchParam);
            })->when($dateSearchParams, function ($query, $dateSearchParams) use ($request) {
                $startDate = Carbon::parse($request->start_date);
                $endDate = Carbon::parse($request->end_date);
                return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
            })->selectRaw('SUM(total_price + shipping_fee) as total_sales')
            ->value('total_sales');

            $totalOrderPlaced = EcommerceOrder::when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
                return $query->where('created_at', '>=', $carbonDateFilter);
            })->when($statusSearchParam, function ($query) use ($statusSearchParam) {
                return $query->where('status',  $statusSearchParam);
            })->when($dateSearchParams, function ($query, $dateSearchParams) use ($request) {
                $startDate = Carbon::parse($request->start_date);
                $endDate = Carbon::parse($request->end_date);
                return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
            })->count();

            $totalItemSold = EcommerceOrderDetails::when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
                return $query->where('created_at', '>=', $carbonDateFilter);
            })->when($statusSearchParam, function ($query) use ($statusSearchParam) {
                return $query->where('status',  $statusSearchParam);
            })->when($dateSearchParams, function ($query, $dateSearchParams) use ($request) {
                $startDate = Carbon::parse($request->start_date);
                $endDate = Carbon::parse($request->end_date);
                return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
            })->sum('quantity_ordered');

            $orders = EcommerceOrder::when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
                return $query->where('created_at', '>=', $carbonDateFilter);
            })->when($nameSearchParam, function ($query) use ($nameSearchParam) {
                return $query->where('orderNO', 'LIKE', '%' . $nameSearchParam . '%');
            })->when($statusSearchParam, function ($query) use ($statusSearchParam) {
                return $query->where('status',  $statusSearchParam);
            })->when($sortByRequestParam, function ($query) use ($request) {
                if (isset($request->sort_by) && $request->sort_by == "alphabetically") {
                    return $query->orderBy('orderNO', 'asc');
                } else if (isset($request->sort_by) && $request->sort_by == "date_old_to_new") {
                    return $query->orderBy('created_at', 'asc');
                } else if (isset($request->sort_by) && $request->sort_by == "date_new_to_old") {
                    return $query->orderBy('created_at', 'desc');
                } else if (isset($request->sort_by) && $request->sort_by == "price_high_to_low") {
                    return $query->orderBy('total_price', 'desc');
                } else if (isset($request->sort_by) && $request->sort_by == "price_low_to_high") {
                    return $query->orderBy('total_price', 'asc');
                } else {
                    return $query->orderBy('created_at', 'desc');
                }
            })->when($dateSearchParams, function ($query, $dateSearchParams) use ($request) {
                $startDate = Carbon::parse($request->start_date);
                $endDate = Carbon::parse($request->end_date);
                return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
            });

            if (isset($request->export)) {
                $orders = $orders->get();
                return Excel::download(new OrderExportReport($orders), 'orderreportdata.xlsx');
            } else {
                $orders = $orders->paginate(12);
            }


            $months = range(1, 12);
            $orderChart = [];
            foreach ($months as $month) {
                $revenueSales = EcommerceOrder::whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', $month)
                    ->sum('total_price');

                $orderPlaced = EcommerceOrder::whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', $month)
                    ->count();

                $itemSold = EcommerceOrderDetails::whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', $month)
                    ->sum('quantity_ordered');

                $orderChart[date('F', mktime(0, 0, 0, $month, 10))] = ["orderPlaced" => $orderPlaced, 'itemSold' => $itemSold];
            }

            $data = [
                'totalOrderPlaced' => $totalOrderPlaced,
                'totalItemSold' => $totalItemSold,
                'totalOrderRevenue' => $totalOrderRevenue,
                'orders' => $orders,
                'orderChart' => $orderChart
            ];

            return JsonResponser::send(false, 'Record(s) found successfully', $data, 200);


        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }


    public function customers(Request $request)
    {
        try {
            if (!auth()->user()->hasPermission('view.ecommercereports')) {
                return JsonResponser::send(true, "Permission Denied :(", [], 401);
            }

            $recordSearchParam = $request->searchByDate;
            $statusSearchParam = $request->status;

            (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;
            $sortByRequestParam = $request->sort_by;
            $customerNameSearchParam = $request->customer_name;
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
            $customerRole = 'ecommercecustomer';

            $totalCustomer = User::whereRelation('roles', 'slug', $customerRole)
                ->when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
                    return $query->where('created_at', '>=', $carbonDateFilter);
                })->when($statusSearchParam, function ($query) use ($statusSearchParam) {
                    return $query->where('is_active',  $statusSearchParam);
                })->when($dateSearchParams, function ($query, $dateSearchParams) use ($request) {
                    $startDate = Carbon::parse($request->start_date);
                    $endDate = Carbon::parse($request->end_date);
                    return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                })->count();

            $totalVisitor = User::whereRelation('roles', 'slug', $customerRole)
                ->when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
                    return $query->where('created_at', '>=', $carbonDateFilter);
                })->when($statusSearchParam, function ($query) use ($statusSearchParam) {
                    return $query->where('is_active',  $statusSearchParam);
                })->when($dateSearchParams, function ($query, $dateSearchParams) use ($request) {
                    $startDate = Carbon::parse($request->start_date);
                    $endDate = Carbon::parse($request->end_date);
                    return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                })->count();

            $customers = User::with('usershipping', 'userbilling')->whereRelation('roles', 'slug', $customerRole)
                ->when($sortByRequestParam, function ($query) use ($request) {
                    if(isset($request->sort_by) && $request->sort_by == "alphabetically"){
                        return $query->orderBy('firstname', 'asc');
                    }else if(isset($request->sort_by) && $request->sort_by == "date_old_to_new"){
                        return $query->orderBy('created_at', 'asc');
                    }else if(isset($request->sort_by) && $request->sort_by == "date_new_to_old"){
                        return $query->orderBy('created_at', 'desc');
                    }else{
                        return $query->orderBy('created_at', 'desc');
                    }
                })->when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
                    return $query->where('created_at', '>=', $carbonDateFilter);
                })->when($customerNameSearchParam, function ($query, $customerNameSearchParam) use ($request) {
                    return $query->where('firstname', 'LIKE', '%' . $customerNameSearchParam . '%');
                })->when($statusSearchParam, function ($query, $statusSearchParam) use ($request) {
                    return $query->where('is_active', $statusSearchParam);
                });
    
            if(isset($request->export)){
                $customers = $customers->get();
                return Excel::download(new CustomerReportExport($customers), 'customersreportdata.xlsx');
            }else{
                $customers = $customers->paginate(12);
                // return JsonResponser::send(false, 'Record found successfully', $customers, 200);
            }

            $months = range(1, 12);
            $customerChart = [];
            foreach ($months as $month) {

                $customerStat = User::whereRelation('roles', 'slug', $customerRole)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', $month)
                    ->count();

                $customerChart[date('F', mktime(0, 0, 0, $month, 10))] = ["customerStat" => $customerStat];
            }

            $data = [
                'totalCustomer' => $totalCustomer,
                'totalVisitor' => $totalVisitor,
                'customers' => $customers,
                'customerChart' => $customerChart
            ];

            return JsonResponser::send(false, 'Record(s) found successfully', $data, 200);

        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }
}
