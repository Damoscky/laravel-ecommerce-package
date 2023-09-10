<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Complaint;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use Maatwebsite\Excel\Facades\Excel;
use SbscPackage\Ecommerce\Exports\ComplaintReportExport;
use Illuminate\Support\Facades\Validator;
use Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SbscPackage\Ecommerce\Helpers\UserMgtHelper;
use SbscPackage\Ecommerce\Helpers\FileUploadHelper;
use App\Models\User;
use SbscPackage\Ecommerce\Interfaces\ComplaintStatusInterface;
use SbscPackage\Ecommerce\Models\EcommerceComplaint;
use SbscPackage\Ecommerce\Models\EcommerceComplaintStatus;

class ComplaintController extends BaseController
{
    /**
     * fetch list of all complaints
     */
    public function listAllComplaints(Request $request)
    {
        if (!auth()->user()->hasPermission('view.ecommercomplaints')) {
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $nameSearchParam = $request->customer_name;
        $orderSearchParam = $request->order_id;
        $statusSearchParam = $request->status;
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->product_start_date) && !is_null($request->product_end_date)) ? $dateSearchParams = true : $dateSearchParams = false;
 
        try {
            $records = EcommerceComplaint::
                when($nameSearchParam, function ($query) use ($nameSearchParam) {
                    return $query->whereHas('customer', function ($query) use ($nameSearchParam) {
                        return $query->where('firstname', 'LIKE', '%'. $nameSearchParam .'%')
                        ->orWhere('lastname', 'LIKE', '%'. $nameSearchParam .'%');
                    });
                })->when($orderSearchParam, function ($query) use ($orderSearchParam) {
                    return $query->whereHas('ecommerceorderdetails', function ($query) use ($orderSearchParam) {
                        return $query->where('orderNO', $orderSearchParam);
                    });
                })->when($sortByRequestParam, function ($query) use ($request) {
                    if (isset($request->sort_by) && $request->sort_by == "alphabetically") {
                        return $query->orderBy('reason', 'asc');
                    } else if (isset($request->sort_by) && $request->sort_by == "date_old_to_new") {
                        return $query->orderBy('created_at', 'asc');
                    } else if (isset($request->sort_by) && $request->sort_by == "date_new_to_old") {
                        return $query->orderBy('created_at', 'desc');
                    } else {
                        return $query->orderBy('created_at', 'desc');
                    }
                })->when($statusSearchParam, function ($query, $statusSearchParam) use ($request) {
                    return $query->where('status', $statusSearchParam);
                });

            if (isset($request->export)) {
                $records = $records->get();
                return Excel::download(new ComplaintReportExport($records), 'categoriesreportdata.xlsx');
            } else {
                $records = $records->paginate(12);
                return JsonResponser::send(false, 'Record found successfully', $records, 200);
            }
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function complaintsStat()
    {
        try {
            $totalComplaints = EcommerceComplaint::count();
            $pendingComplaints = EcommerceComplaint::where('status', ComplaintStatusInterface::PENDING)->count();
            $resolvedComplaints = EcommerceComplaint::where('status', ComplaintStatusInterface::RESOLVED)->count();

            $data = [
                'totalComplaints' => $totalComplaints,
                'pendingComplaints' => $pendingComplaints,
                'resolvedComplaints' => $resolvedComplaints,
            ];

            return JsonResponser::send(false, 'Record found successfully', $data, 200);
        } catch (\Throwable $th) {
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    public function show($id)
    {
        if(!auth()->user()->hasPermission('view.ecommercomplaints')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        try {
            $record = EcommerceComplaint::where('id', $id)->first();

            if (!$record) {
                return JsonResponser::send(true, "Record not found.", [], 400);
            }

            return JsonResponser::send(false, 'Record found successfully', $record);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    public function update(Request $request, $id)
    {
        if(!auth()->user()->hasPermission('manage.ecommercomplaints')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        try {
            $record = EcommerceComplaint::where('id', $id)->first();

            if (!$record) {
                return JsonResponser::send(true, "Record not found.", [], 400);
            }

            $attachment = FileUploadHelper::singleStringFileUpload($request->attachment, "EcommerceComplain");

            $currentUserInstance = auth()->user();

            $newRecord = EcommerceComplaintStatus::create([
                'ecommerce_complaint_id' => $record->id,
                'user_id' => $currentUserInstance->id,
                'comment' => $request->comment,
                'previous_status' => $record->status,
                'status' => $request->status,
                'attachment' => $attachment
            ]);
            
            $record->update([
                'status' => $request->status,
                'priority' => $request->priority,
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $newRecord->id,
                'action_type' => "Models\EcommerceComplaint",
                'log_name' => "EcommerceComplaint updated Successfully",
                'action' => 'Updated',
                'description' => "{$currentUserInstance->lastname} {$currentUserInstance->firstname} updated EcommerceComplaint Successfully",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            return JsonResponser::send(false, 'Record updated successfully', $newRecord);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }
    
}
