<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Dashboard;

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

class DashboardController extends BaseController
{

    public function dashboard(Request $request)
    {
        $recordSearchParam = $request->searchByDate;
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

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;


        $customerRole = 'ecommercecustomer';
        $vendorRole = 'ecommercevendors';
    
        $totalProductSales = EcommerceOrder::when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
            return $query->where('created_at', '>=', $carbonDateFilter);
        })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
        })->sum('total_price');
        $totalProducts = EcommerceProduct::when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
            return $query->where('created_at', '>=', $carbonDateFilter);
        })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
        })->count();
        $totalCustomers = User::whereHas('roles', function ($roleTable) use ($customerRole) {
            $roleTable->where('slug', $customerRole);
        })->when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
            return $query->where('created_at', '>=', $carbonDateFilter);
        })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
        })->count();
        $recentCustomers = User::whereHas('roles', function ($roleTable) use ($customerRole) {
            $roleTable->where('slug', $customerRole);
        })->when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
            return $query->where('created_at', '>=', $carbonDateFilter);
        })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
        })->take(5)->get();
        $totalVendors = User::whereHas('roles', function ($roleTable) use ($vendorRole) {
            $roleTable->where('slug', $vendorRole);
        })->when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
            return $query->where('created_at', '>=', $carbonDateFilter);
        })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
        })->count();
        $topVendors = User::whereHas('roles', function ($roleTable) use ($vendorRole) {
            $roleTable->where('slug', $vendorRole);
        })->when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
            return $query->where('created_at', '>=', $carbonDateFilter);
        })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
        })->take(5)->get();
        $totalOrders = EcommerceOrder::when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
            return $query->where('created_at', '>=', $carbonDateFilter);
        })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
        })->count();
        $recentOrders = EcommerceOrder::when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
            return $query->where('created_at', '>=', $carbonDateFilter);
        })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
        })->take(5)->get();

        $activeSubscription = 0;

        $topProducts = EcommerceOrderDetails::with('ecommerceproduct')->whereHas('ecommerceproduct', function ($query) {
            $query->where('status', ProductStatusInterface::ACTIVE);
        })->when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
            return $query->where('created_at', '>=', $carbonDateFilter);
        })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
        })->orderBy('quantity_ordered', 'DESC')->take(5)->get();

        $deliveredSales =  EcommerceOrder::when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
            return $query->where('created_at', '>=', $carbonDateFilter);
        })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
        })->where('status', OrderStatusInterface::DELIVERED)->count();
        $returnSales =  EcommerceOrder::when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
            return $query->where('created_at', '>=', $carbonDateFilter);
        })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
        })->where('status', OrderStatusInterface::RETURNED)->count();
        $cancelledSales =  EcommerceOrder::when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
            return $query->where('created_at', '>=', $carbonDateFilter);
        })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
        })->where('status', OrderStatusInterface::CANCELLED)->count();
        $totalSales = EcommerceOrder::when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
            return $query->where('created_at', '>=', $carbonDateFilter);
        })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
        })->count();
        $activeSales = EcommerceOrder::when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
            return $query->where('created_at', '>=', $carbonDateFilter);
        })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
        })->where('status', OrderStatusInterface::PROCESSING)->orWhere('status', OrderStatusInterface::PENDING)->orWhere('status', OrderStatusInterface::SHIPPED)->count();
        $salesAnalysis = [
            'returnSales' => $returnSales,
            'deliveredSales' => $deliveredSales,
            'cancelledSales' => $cancelledSales,
            'totalSales' => $totalSales,
            'activeSales' => $activeSales,
        ];

        $months = range(1, 12);
        $revenueStat = [];
        foreach ($months as $month) {
            $revenueSales = EcommerceOrder::whereMonth('created_at', $month)
                ->whereYear('created_at', Carbon::now()->year)->sum('total_price');

            $orders = EcommerceOrder::whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', $month)
                ->count();

            $customers = User::whereRelation('roles', 'slug', $customerRole)
            ->whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', $month)
            ->count();

            $revenueStat[date('F', mktime(0, 0, 0, $month, 10))] = ["orders" => $orders, 'customers' => $customers];
        }

        $data = [
            'totalProductSales' => $totalProductSales,
            'totalProducts' => $totalProducts,
            'totalCustomers' => $totalCustomers,
            'recentCustomers' => $recentCustomers,
            'totalVendors' => $totalVendors,
            'topVendors' => $topVendors,
            'totalOrders' => $totalOrders,
            'recentOrders' => $recentOrders,
            'activeSubscription' => $activeSubscription,
            'topProducts' => $topProducts,
            'salesAnalysis' => $salesAnalysis,
            'revenueStat' => $revenueStat
        ];

        return JsonResponser::send(false, 'Record found successfully', $data, 200);

    }

}
