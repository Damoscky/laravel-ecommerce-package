<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Customer;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use Maatwebsite\Excel\Facades\Excel;
use SbscPackage\Ecommerce\Exports\CustomerReportExport;
use Illuminate\Support\Facades\Validator;
use Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SbscPackage\Ecommerce\Helpers\UserMgtHelper;
use SbscPackage\Ecommerce\Helpers\FileUploadHelper;
use App\Models\User;
use SbscPackage\Ecommerce\Models\EcommerceOrder;
use SbscPackage\Ecommerce\Models\EcommerceOrderDetails;

class CustomerController extends BaseController
{
    public function index(Request $request)
    {
        if(!auth()->user()->hasPermission('view.ecommercecustomers')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $customerNameSearchParam = $request->customer_name;
        $statusSearchParam = $request->status;
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->product_start_date) && !is_null($request->product_end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        try {
            $roleName = "ecommercecustomer";
            $customers = User::with('usershipping', 'userbilling')->whereHas('roles', function ($roleTable) use ($roleName) {
                return $roleTable->where('slug', $roleName);
            })->when($sortByRequestParam, function ($query) use ($request) {
                if(isset($request->sort_by) && $request->sort_by == "alphabetically"){
                    return $query->orderBy('firstname', 'asc');
                }else if(isset($request->sort_by) && $request->sort_by == "date_old_to_new"){
                    return $query->orderBy('created_at', 'asc');
                }else if(isset($request->sort_by) && $request->sort_by == "date_new_to_old"){
                    return $query->orderBy('created_at', 'desc');
                }else{
                    return $query->orderBy('created_at', 'desc');
                }
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
                return JsonResponser::send(false, 'Record found successfully', $customers, 200);
            }

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    public function indexCRM(Request $request)
    {
        if(!auth()->user()->hasPermission('view.ecommercecustomers')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $customerNameSearchParam = $request->customer_name;
        $statusSearchParam = $request->status;
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->product_start_date) && !is_null($request->product_end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        try {
            $roleName = "crmcustomer";
            $customers = User::with('usershipping', 'userbilling', 'institutionCustomer')->whereHas('roles', function ($roleTable) use ($roleName) {
                return $roleTable->where('slug', $roleName);
            })->when($sortByRequestParam, function ($query) use ($request) {
                if(isset($request->sort_by) && $request->sort_by == "alphabetically"){
                    return $query->orderBy('firstname', 'asc');
                }else if(isset($request->sort_by) && $request->sort_by == "date_old_to_new"){
                    return $query->orderBy('created_at', 'asc');
                }else if(isset($request->sort_by) && $request->sort_by == "date_new_to_old"){
                    return $query->orderBy('created_at', 'desc');
                }else{
                    return $query->orderBy('created_at', 'desc');
                }
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
                return JsonResponser::send(false, 'Record found successfully', $customers, 200);
            }

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }
    public function activeCustomers(Request $request)
    {
        if(!auth()->user()->hasPermission('view.ecommercecustomers')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $customerNameSearchParam = $request->customer_name;
        $statusSearchParam = $request->status;
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->product_start_date) && !is_null($request->product_end_date)) ? $dateSearchParams = true : $dateSearchParams = false;


        try {
            $roleName = "ecommercecustomer";
            $customers = User::with('usershipping', 'userbilling')->whereHas('roles', function ($roleTable) use ($roleName) {
                return $roleTable->where('slug', $roleName);
            })->when($sortByRequestParam, function ($query) use ($request) {
                if(isset($request->sort_by) && $request->sort_by == "alphabetically"){
                    return $query->orderBy('firstname', 'asc');
                }else if(isset($request->sort_by) && $request->sort_by == "date_old_to_new"){
                    return $query->orderBy('created_at', 'asc');
                }else if(isset($request->sort_by) && $request->sort_by == "date_new_to_old"){
                    return $query->orderBy('created_at', 'desc');
                }else{
                    return $query->orderBy('created_at', 'desc');
                }
            })->when($customerNameSearchParam, function ($query, $customerNameSearchParam) use ($request) {
                return $query->where('firstname', 'LIKE', '%' . $customerNameSearchParam . '%');
            })->when($statusSearchParam, function ($query, $statusSearchParam) use ($request) {
                return $query->where('status', $statusSearchParam);
            })->where('is_active', true);

            foreach ($customers as $record) {
                $totalOrders = EcommerceOrderDetails::where('user_id', $record->id)->count();
                $customers->totalOrders = $totalOrders;
            }

            if(isset($request->export)){
                $customers = $customers->get();
                return Excel::download(new CustomerReportExport($customers), 'customersreportdata.xlsx');
            }else{
                $customers = $customers->paginate(12);
                return JsonResponser::send(false, 'Record found successfully', $customers, 200);
            }

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    public function inactiveCustomers(Request $request)
    {
        if(!auth()->user()->hasPermission('view.ecommercecustomers')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $customerNameSearchParam = $request->customer_name;
        $statusSearchParam = $request->status;
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->product_start_date) && !is_null($request->product_end_date)) ? $dateSearchParams = true : $dateSearchParams = false;


        try {
            $roleName = "ecommercecustomer";
            $customers = User::with('usershipping', 'userbilling')->whereHas('roles', function ($roleTable) use ($roleName) {
                return $roleTable->where('slug', $roleName);
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
            })->when($customerNameSearchParam, function ($query, $customerNameSearchParam) use ($request) {
                return $query->where('firstname', 'LIKE', '%' . $customerNameSearchParam . '%');
            })->when($statusSearchParam, function ($query, $statusSearchParam) use ($request) {
                return $query->where('status', $statusSearchParam);
            })->where('is_active', false);

            if(isset($request->export)){
                $customers = $customers->get();
                return Excel::download(new CustomerReportExport($customers), 'customersreportdata.xlsx');
            }else{
                $customers = $customers->paginate(12);
                return JsonResponser::send(false, 'Record found successfully', $customers, 200);
            }

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function show($id)
    {
        if(!auth()->user()->hasPermission('view.ecommercecustomers')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        
        $user = User::with('usershipping', 'userbilling', 'institutionCustomer')->where('id', $id)->first();
        $totalOrders = EcommerceOrderDetails::where('user_id', $user->id)->count();
        $totalSpending = EcommerceOrder::where('user_id', $user->id)->sum('total_price');
        $user->totalOrders = $totalOrders;
        $user->totalSpending = $totalSpending;

        if(is_null($user)){
            return JsonResponser::send(true, 'Record not found', [], 400);
        }

        return JsonResponser::send(false, 'Record found successfully', $user, 200);

    }

    public function updateCustomerStatus(Request $request, $id)
    {
        if(!auth()->user()->hasPermission('edit.ecommercecustomers')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        
        $user = User::find($id);

        if(is_null($user)){
            return JsonResponser::send(true, 'Record not found', [], 400);
        }

        $user->update([
            'is_active' => $request->status
        ]);

        if($request->status == 0){
            return JsonResponser::send(false, 'Customer Deactivated successfully', $user, 200);
        }else{
            return JsonResponser::send(false, 'Customer Activated successfully', $user, 200);
        }

        

    }

    public function customerStat()
    {
        try {
            $roleName = "ecommercecustomer";
            $totalActiveCustomers = User::whereHas('roles', function ($roleTable) use ($roleName) {
                return $roleTable->where('slug', $roleName);
            })->where('is_active', true)->count();
            $totalInactiveCustomers = User::whereHas('roles', function ($roleTable) use ($roleName) {
                return $roleTable->where('slug', $roleName);
            })->where('is_active', false)->count();
            $totalCustomers = User::whereHas('roles', function ($roleTable) use ($roleName) {
                return $roleTable->where('slug', $roleName);
            })->count();

            $data = [
                'totalActiveCustomers' => $totalActiveCustomers,
                'totalInactiveCustomers' => $totalInactiveCustomers,
                'totalCustomers' => $totalCustomers,
            ];

            return JsonResponser::send(false, 'Customer Stats', $data, 200);
        } catch (\Throwable $th) {
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

}