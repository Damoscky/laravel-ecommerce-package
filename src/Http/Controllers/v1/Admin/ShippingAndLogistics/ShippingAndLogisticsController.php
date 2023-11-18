<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\ShippingAndLogistics;

use SbscPackage\Ecommerce\Exports\ShippingOrderReportExport;
use SbscPackage\Ecommerce\Exports\LogisticOrderReportExport;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Helpers\UserMgtHelper;
use SbscPackage\Ecommerce\Interfaces\OrderStatusInterface;
use SbscPackage\Ecommerce\Interfaces\PaymentStatusInterface;
use SbscPackage\Ecommerce\Models\EcommerceOrder;
use SbscPackage\Ecommerce\Models\EcommerceOrderDetails;
// use SbscPackage\Ecommerce\Models\EcommerceOrderLogistics;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use Illuminate\Routing\Controller as BaseController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class ShippingAndLogisticsController extends BaseController
{
    //index for shipping orders
    public function index(Request $request)
    {
        $productNameSearchParam = $request->product_name;
        $storeSearchParam = $request->store_id;
        $orderNumberSearchParam  = $request->order_number;
        $customerNameSearchParam = $request->customer_name;
        $status = $request->status;
        $sortByRequestParam = $request->sort_by;

        $alphabetically = $request->alphabetically;
        $dateOldToNew = $request->date_old_to_new;
        $dateNewToOld = $request->date_new_to_old;
        $priceLowToHigh = $request->price_low_to_high;
        $priceHighToLow = $request->price_high_to_low;

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        if (!isset($request->sort)) {
            $sort = 'ASC';
        } else if ($request->sort == 'asc') {
            $sort = 'ASC';
        } else if ($request->sort == 'desc') {
            $sort = 'DESC';
        }

        $status === "TOTAL ORDERS" ? $status = false : $status;

        try {
            $orders = EcommerceOrder::whereHas('ecommerceorderdetails', function ($query){
                return $query->whereNull('ecommerce_order_details.logistics_company_id');
            })
            ->where('payment_status', PaymentStatusInterface::SUCCESS)
            ->with('ecommerceorderdetails', 'user', 'user.usershipping')
            ->when($customerNameSearchParam, function ($query, $customerNameSearchParam) {
                    return $query->where('fullname', 'like', '%' . $customerNameSearchParam . '%');
            })
            ->when($storeSearchParam, function ($query, $storeSearchParam) {
                return $query->whereHas('order', function ($query) use ($storeSearchParam) {
                    return $query->where('store_id', $storeSearchParam);
                });
            })
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
            })
            ->when($orderNumberSearchParam, function ($query, $orderNumberSearchParam) {
                return $query->where('orderNO', 'like', '%' . $orderNumberSearchParam . '%');
            })
            ->when($productNameSearchParam, function ($query, $productNameSearchParam) {
                return $query->whereHas('product', function ($query) use ($productNameSearchParam) {
                    return $query->where('product_name', 'like', '%' . $productNameSearchParam . '%');
                });
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })

            ->when($alphabetically, function ($query) {
                return $query->orderBy('product_name', 'asc');
            })
            ->when($dateOldToNew, function ($query) {
                return $query->orderBy('created_at', 'asc');
            })
            ->when($dateNewToOld, function ($query) {
                return $query->orderBy('created_at', 'desc');
            })
            ->when($priceLowToHigh, function ($query) {
                return $query->orderBy('total_price', 'asc');
            })
            ->when($priceHighToLow, function ($query) {
                return $query->orderBy('total_price', 'desc');
            })
            ->orderBy('created_at', $sort)
            ->paginate(10);
            if (!$orders) {
                return JsonResponser::send(true, 'No record(s) found!', [], 400);
            }
            return JsonResponser::send(false, 'Record(s) found successfully', $orders, 200);
        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }

    public function assignedLogisticOrders(Request $request)
    {
        $productNameSearchParam = $request->product_name;
        $storeSearchParam = $request->store_id;
        $orderNumberSearchParam  = $request->order_number;
        $customerNameSearchParam = $request->customer_name;
        $status = $request->status;

        $alphabetically = $request->alphabetically;
        $dateOldToNew = $request->date_old_to_new;
        $dateNewToOld = $request->date_new_to_old;
        $priceLowToHigh = $request->price_low_to_high;
        $priceHighToLow = $request->price_high_to_low;

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        if (!isset($request->sort)) {
            $sort = 'ASC';
        } else if ($request->sort == 'asc') {
            $sort = 'ASC';
        } else if ($request->sort == 'desc') {
            $sort = 'DESC';
        }

        $status === "TOTAL ORDERS" ? $status = false : $status;

        try {
            $orders = EcommerceOrder::whereHas('ecommerceorderdetails', function ($query){
                return $query->whereNotNull('order_details.logistics_company_id');
            })
            ->where('payment_status', PaymentStatusInterface::SUCCESS)
                ->with('ecommerceorderdetails', 'user', 'user.usershipping')
                ->when($customerNameSearchParam, function ($query, $customerNameSearchParam) {
                        return $query->where('fullname', 'like', '%' . $customerNameSearchParam . '%');
                })
                ->when($storeSearchParam, function ($query, $storeSearchParam) {
                    return $query->whereHas('order', function ($query) use ($storeSearchParam) {
                        return $query->where('store_id', $storeSearchParam);
                    });
                })
                ->when($orderNumberSearchParam, function ($query, $orderNumberSearchParam) {
                    return $query->where('orderNO', 'like', '%' . $orderNumberSearchParam . '%');
                })
                ->when($productNameSearchParam, function ($query, $productNameSearchParam) {
                    return $query->whereHas('product', function ($query) use ($productNameSearchParam) {
                        return $query->where('product_name', 'like', '%' . $productNameSearchParam . '%');
                    });
                })
                ->when($status, function ($query, $status) {
                    return $query->where('status', $status);
                })

                ->when($alphabetically, function ($query) {
                    return $query->orderBy('product_name', 'asc');
                })
                ->when($dateOldToNew, function ($query) {
                    return $query->orderBy('created_at', 'asc');
                })
                ->when($dateNewToOld, function ($query) {
                    return $query->orderBy('created_at', 'desc');
                })
                ->when($priceLowToHigh, function ($query) {
                    return $query->orderBy('total_price', 'asc');
                })
                ->when($priceHighToLow, function ($query) {
                    return $query->orderBy('total_price', 'desc');
                })
                ->orderBy('created_at', $sort)
                ->paginate(10);
            if (!$orders) {
                return JsonResponser::send(true, 'No record(s) found!', [], 400);
            }
            return JsonResponser::send(false, 'Record(s) found successfully', $orders, 200);
        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }

    public function ordersWithLogistic(Request $request)
    {
        $productNameSearchParam = $request->product_name;
        $orderNumberSearchParam  = $request->order_number;
        $customerNameSearchParam = $request->customer_name;
        $status = $request->status;
        $sortByRequestParam = $request->sort_by;

        $alphabetically = $request->alphabetically;
        $dateOldToNew = $request->date_old_to_new;
        $dateNewToOld = $request->date_new_to_old;
        $priceLowToHigh = $request->price_low_to_high;
        $priceHighToLow = $request->price_high_to_low;

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        if (!isset($request->sort)) {
            $sort = 'ASC';
        } else if ($request->sort == 'asc') {
            $sort = 'ASC';
        } else if ($request->sort == 'desc') {
            $sort = 'DESC';
        }

        $status === "TOTAL ORDERS" ? $status = false : $status;

        try {
            $orders = EcommerceOrderDetails::where('payment_status', PaymentStatusInterface::SUCCESS)
                ->with('ecommerceorder', 'user', 'user.usershipping', 'logisticsCompany')
                ->when($customerNameSearchParam, function ($query, $customerNameSearchParam) {
                    return $query->whereHas('ecommerceorder', function ($query) use ($customerNameSearchParam) {
                        return $query->where('fullname', 'like', '%' . $customerNameSearchParam . '%');
                    });
                })
                ->when($orderNumberSearchParam, function ($query, $orderNumberSearchParam) {
                    return $query->where('orderNO', 'like', '%' . $orderNumberSearchParam . '%');
                })
                ->when($productNameSearchParam, function ($query, $productNameSearchParam) {
                    return $query->whereHas('product', function ($query) use ($productNameSearchParam) {
                        return $query->where('product_name', 'like', '%' . $productNameSearchParam . '%');
                    });
                })
                ->when($sortByRequestParam, function ($query, $customerNameSearchParam) use ($request) {
                    if(isset($request->sort_by) && $request->sort_by == "alphabetically"){
                        return $query->whereHas('ecommerceorder', function ($query) use ($customerNameSearchParam) {
                            return $query->orderBy('fullname', 'desc');
                        });
                    }else if(isset($request->sort_by) && $request->sort_by == "date_old_to_new"){
                        return $query->orderBy('created_at', 'asc');
                    }else if(isset($request->sort_by) && $request->sort_by == "date_new_to_old"){
                        return $query->orderBy('created_at', 'desc');
                    }else{
                        return $query->orderBy('created_at', 'desc');
                    }
                })
                ->when($status, function ($query, $status) {
                    return $query->where('status', $status);
                })

                ->when($alphabetically, function ($query) {
                    return $query->orderBy('product_name', 'asc');
                })
                ->when($dateOldToNew, function ($query) {
                    return $query->orderBy('created_at', 'asc');
                })
                ->when($dateNewToOld, function ($query) {
                    return $query->orderBy('created_at', 'desc');
                })
                ->when($priceLowToHigh, function ($query) {
                    return $query->orderBy('total_price', 'asc');
                })
                ->when($priceHighToLow, function ($query) {
                    return $query->orderBy('total_price', 'desc');
                })
                ->whereNotNull('logistics_company_id')
                ->orderBy('updated_at', 'DESC');
            if (!$orders) {
                return JsonResponser::send(true, 'No record(s) found!', [], 400);
            }
            if(isset($request->export)){
                $orders = $orders->get();
                return Excel::download(new LogisticOrderReportExport($orders), 'logisticorderreportdata.xlsx');
            }
            $orders = $orders->paginate(10);
            return JsonResponser::send(false, 'Record(s) found successfully', $orders, 200);
        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }

    public function shippingOrdersStat()
    {
        try {
            $orders = EcommerceOrderDetails::count();
            $deliveredOrders = EcommerceOrderDetails::where('status', OrderStatusInterface::DELIVERED)->count();
            $processingorders = EcommerceOrderDetails::where('status', OrderStatusInterface::PROCESSING)->count();
            $cancelledOrders = EcommerceOrderDetails::where('status', OrderStatusInterface::CANCELLED)->count();
            $enrouteOrders = EcommerceOrderDetails::where('status', OrderStatusInterface::ENROUTE)->count();

            $data = [
                'totalOrders' => $orders,
                'deliveredOrders' => $deliveredOrders,
                'processingorders' => $processingorders,
                'cancelledOrders' => $cancelledOrders,
                'enrouteOrders' => $enrouteOrders
            ];

            return JsonResponser::send(false, 'Order stat Data', $data, 200);
        } catch (\Throwable $th) {
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $order = EcommerceOrderDetails::find($id);

        if (!$order) {
            return JsonResponser::send(true, 'Order not found', null);
        }

        try {
            DB::beginTransaction();

            $order->update([
                'status' => $request->status
            ]);

            $currentUserInstance = auth()->user();

            $dataToLog = [
                'causer_id' => auth()->user()->id,
                'action_id' => $order->id,
                'action_type' => "Models\Order",
                'log_name' => "Order activated Successfully",
                'action' => 'Update',
                'description' => "Order activated Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Order Status Updated Successfully!', $order);
        } catch (\Throwable $th) {
            logger($th);
            DB::rollBack();
            return JsonResponser::send(true, 'Internal Server error', [], 500);
        }
    }

    public function exportShippingOrders(Request $request)
    {
        $productNameSearchParam = $request->product_name;
        $orderNumberSearchParam  = $request->order_number;
        $customerNameSearchParam = $request->customer_name;
        $status = $request->status;

        $alphabetically = $request->alphabetically;
        $dateOldToNew = $request->date_old_to_new;
        $dateNewToOld = $request->date_new_to_old;
        $priceLowToHigh = $request->price_low_to_high;
        $priceHighToLow = $request->price_high_to_low;

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        if (!isset($request->sort)) {
            $sort = 'ASC';
        } else if ($request->sort == 'asc') {
            $sort = 'ASC';
        } else if ($request->sort == 'desc') {
            $sort = 'DESC';
        }

        $status === "TOTAL ORDERS" ? $status = false : $status;

        try {
            $orders = EcommerceOrderDetails::where('payment_status', PaymentStatusInterface::SUCCESS)
                ->with('ecommerceorder', 'user', 'ecommerceproduct', 'logisticsCompany')
                ->when($customerNameSearchParam, function ($query, $customerNameSearchParam) {
                    return $query->whereHas('user', function ($query) use ($customerNameSearchParam) {
                        return $query->where('firstname', 'like', '%' . $customerNameSearchParam . '%');
                    });
                })
                ->when($customerNameSearchParam, function ($query, $customerNameSearchParam) {
                    return $query->whereHas('user', function ($query) use ($customerNameSearchParam) {
                        return $query->where('lastname', 'like', '%' . $customerNameSearchParam . '%');
                    });
                })
                ->when($orderNumberSearchParam, function ($query, $orderNumberSearchParam) {
                    return $query->where('orderID', 'like', '%' . $orderNumberSearchParam . '%');
                })
                ->when($productNameSearchParam, function ($query, $productNameSearchParam) {
                    return $query->whereHas('product', function ($query) use ($productNameSearchParam) {
                        return $query->where('product_name', 'like', '%' . $productNameSearchParam . '%');
                    });
                })
                ->when($status, function ($query, $status) {
                    return $query->where('status', $status);
                })
                ->when($alphabetically, function ($query) {
                    return $query->orderBy('product_name', 'desc');
                })
                ->when($dateOldToNew, function ($query) {
                    return $query->orderBy('created_at', 'asc');
                })
                ->when($dateNewToOld, function ($query) {
                    return $query->orderBy('created_at', 'desc');
                })
                ->when($priceLowToHigh, function ($query) {
                    return $query->orderBy('total_price', 'asc');
                })
                ->when($priceHighToLow, function ($query) {
                    return $query->orderBy('total_price', 'desc');
                })
                ->get();
            if (!$orders) {
                return JsonResponser::send(true, 'No record(s) found!', [], 400);
            }
            return Excel::download(new ShippingOrderReportExport($orders), 'shippingorderreportdata.xlsx');
        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }

    public function attachLogisticsCompany(Request $request, $id)
    {
        $orders = EcommerceOrder::find($id);

        if (!$orders) {
            return JsonResponser::send(true, 'Order Details Not Found', []);
        }

        $userInstance = UserMgtHelper::userInstance();
        $userId = $userInstance->id;


        try {
            DB::beginTransaction();

            $orderDetails = EcommerceOrderDetails::where('ecommerce_order_id', $id)->get();

            foreach ($orderDetails as $key => $value) {
                $value->update([
                    'logistics_company_id' => $request->logistics_company_id,
                    'tracking_number' => $request->tracking_number,
                    'status' => "Shipped",
                ]);
            }

            $orders->update([
                'status' => "Shipped"
            ]);
           
            $currentUserInstance = auth()->user();

            $dataToLog = [
                'causer_id' => auth()->user()->id,
                'action_id' => $orders->id,
                'action_type' => "Models\LogisticsCompany",
                'log_name' => "Order details updated Successfully",
                'action' => 'Update',
                'description' => "Order details updated Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Order Details Updated Successfully!', $orders);
        } catch (\Throwable $th) {
            logger($th);
            DB::rollBack();
            return JsonResponser::send(true, $th->getMessage(), [], 500);
        }
    }
}
