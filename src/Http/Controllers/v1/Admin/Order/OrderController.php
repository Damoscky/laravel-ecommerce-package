<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Order;

use Illuminate\Routing\Controller as BaseController;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB, Validator;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use SbscPackage\Ecommerce\Exports\OrderExportReport;
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
            $totalOrders = EcommerceOrder::count();
            $completedOrders = EcommerceOrder::where('status', OrderStatusInterface::DELIVERED)->count();
            $processingOrders = EcommerceOrder::where('status', OrderStatusInterface::PROCESSING)->count();
            $cancelledOrders = EcommerceOrder::where('status', OrderStatusInterface::CANCELLED)->count();


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
            });

            if(isset($request->export)){
                $orders = $orders->get();
                return Excel::download(new OrderExportReport($orders), 'orderreportdata.xlsx');
            }else{
                $orders = $orders->paginate(12);
            }

            $data = [
                'orders' => $orders,
                'totalOrders' => $totalOrders,
                'completedOrders' => $completedOrders,
                'processingOrders' => $processingOrders,
                'cancelledOrders' => $cancelledOrders,
            ];

            return JsonResponser::send(false, 'Record(s) found successfully', $data, 200);

        } catch (\Throwable $error) {
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }


    public function show($id)
    {
        $record = EcommerceOrder::with('ecommerceorderdetails.ecommerceproduct', 'ecommerceshippingaddress', 'ecommercebillingdetails')->where('id', $id)->first();
        return JsonResponser::send(false, 'Record(s) found successfully', $record, 200);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $order = EcommerceOrder::find($id);
        // $order = OrderDetails::find($id);

        if (!$order) {
            return JsonResponser::send(true, 'Order Not Found', null);
        }

        /**
         * Validate Request
         */
        $validate = $this->validateOrder($request);
        /**
         * if validation fails
         */
        if ($validate->fails()) {
            return JsonResponser::send(true, 'Validation failed', $validate->errors()->all());
        }

        try {
            DB::beginTransaction();

            $order->update([
                'status' => $request->status,
            ]);

            foreach ($order->ecommerceorderdetails as $details) {
                $details->update([
                    'status' => $request->status
                ]);
            }

            // $data = [
            //     'orderID' => $order->orderID,
            //     'orderdetails' => $order->orderdetails,
            //     'name' => $order->firstname,
            //     'orders' => $order,
            //     'email' => $order->email,
            // ];

            // if ($request->status === OrderStatusInterface::PROCESSING) {
            //     Mail::to($data['email'])->send(new ProcessingOrderEmail($data));
            // } elseif ($request->status === OrderStatusInterface::ENROUTE) {
            //     Mail::to($data['email'])->send(new ShippedOrderEmail($data));
            // } elseif ($request->status === OrderStatusInterface::COMPLETED) {
            //     Mail::to($data['email'])->send(new CompletedOrderEmail($data));
            // } elseif ($request->status === OrderStatusInterface::CANCELLED) {
            //     Mail::to($data['email'])->send(new CancelledOrderEmail($data));
            // }

            DB::commit();
            return JsonResponser::send(false, 'Order Updated Successfully!', $order, 201);
        } catch (\Throwable $error) {
            logger($error);
            DB::rollback();
            // return JsonResponser::send(true, 'Internal server error', null, 500);
            return JsonResponser::send(true, $error->getMessage(), null, 500);
        }
    }

    public function validateOrder(Request $request)
    {
        $rules = [
            // "status" => "in:received|cancelled|shipped"
            "status" => "required"
        ];
        $validateOrder = Validator::make($request->all(), $rules);
        return $validateOrder;
    }

}