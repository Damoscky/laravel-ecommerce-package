<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\ActivityLog;

use Illuminate\Routing\Controller as BaseController;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use SbscPackage\Ecommerce\Exports\AuditLogsReportExport;
use Maatwebsite\Excel\Facades\Excel;

class ActivityLogController extends BaseController
{
    public function index(Request $request)
    {
        if(!auth()->user()->hasPermission('view.auditlogs')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        $nameSearchParam = $request->username;
        $activitySearchParam = $request->activity;
        $sortByRequestParam = $request->sort_by;

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
        } else {
            $carbonDateFilter = false;
        }

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        try {
            
            $records = AuditLog::with('causer')->where('package_type', 'SbscPackage\Ecommerce')
                ->when($nameSearchParam, function ($query, $nameSearchParam) use ($request) {
                    return $query->whereHas('causer', function ($query) use ($nameSearchParam) {
                        return $query->where('firstname', 'LIKE', '%' . $nameSearchParam . '%');
                    });
                })->when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
                    return $query->where('created_at', '>=', $carbonDateFilter);
                })->when($activitySearchParam, function ($query) use ($activitySearchParam) {
                    return $query->where('action', $activitySearchParam);
                }) ->when($sortByRequestParam, function ($query) use ($request) {
                    if(isset($request->sort_by) && $request->sort_by == "alphabetically"){
                        return $query->orderBy('action', 'asc');
                    }else if(isset($request->sort_by) && $request->sort_by == "date_old_to_new"){
                        return $query->orderBy('created_at', 'asc');
                    }else if(isset($request->sort_by) && $request->sort_by == "date_new_to_old"){
                        return $query->orderBy('created_at', 'desc');
                    }else{
                        return $query->orderBy('created_at', 'desc');
                    }
                });

                if(isset($request->export)){
                    $records = $records->get();
                    return Excel::download(new AuditLogsReportExport($records), 'auditlogsreportdata.xlsx');
                }else{
                    $records = $records->paginate(10);
                    return JsonResponser::send(false, 'Record found successfully', $records, 200);
                }

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function show($id)
    {
        $records = AuditLog::with('causer')->where('package_type', 'SbscPackage\Ecommerce')->where('id', $id)->first();

        if(is_null($records)){
            return JsonResponser::send(true, 'Record not found', [], 400);
        }
        return JsonResponser::send(false, 'Record found successfully', $records);
    }
}
