<?php

namespace App\Http\Controllers\v1\Admin\ActivityLog;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Helpers\ProcessAuditLog;
use App\Responser\JsonResponser;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $nameSearchParam = $request->username;
        $alphabetically = $request->alphabetically;
        $dateOldToNew = $request->date_old_to_new;
        $dateNewToOld = $request->date_new_to_old;

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
            return \Session::get('userInstance');
            $records = AuditLog::
                when($nameSearchParam, function ($query, $nameSearchParam) use ($request) {
                    return $query->whereHas('causer', function ($query) use ($nameSearchParam) {
                        return $query->where('firstname', 'LIKE', '%' . $nameSearchParam . '%');
                    });
                })->when($carbonDateFilter, function ($query) use ($carbonDateFilter) {
                    return $query->where('created_at', '>=', $carbonDateFilter);
                })->when($alphabetically, function ($query) {
                    return $query->orderBy('name', 'ASC');
                })->when($dateOldToNew, function ($query) {
                    return $query->orderBy('id', 'asc');
                })->when($dateNewToOld, function ($query) {
                    return $query->orderBy('id', 'desc');
                })->paginate(10);

            return JsonResponser::send(false, 'Record found successfully', $records);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }
}
