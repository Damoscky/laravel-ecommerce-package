<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Plan;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use Maatwebsite\Excel\Facades\Excel;
use SbscPackage\Ecommerce\Exports\PlanReportExport;
use Illuminate\Support\Facades\Validator;
use Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SbscPackage\Ecommerce\Helpers\UserMgtHelper;
use SbscPackage\Ecommerce\Helpers\FileUploadHelper;
use App\Models\User;
use SbscPackage\Ecommerce\Models\EcommercePlan;

class PlanController extends BaseController
{
    public function listAllPlan(Request $request)
    {
        if(!auth()->user()->hasPermission('view.ecommerceplans')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $nameSearchParam = $request->plan_name;
        $statusSearchParam = $request->status;
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->product_start_date) && !is_null($request->product_end_date)) ? $dateSearchParams = true : $dateSearchParams = false;


        try {
            $records = EcommercePlan::when($sortByRequestParam, function ($query) use ($request) {
                if(isset($request->sort_by) && $request->sort_by == "alphabetically"){
                    return $query->orderBy('name', 'asc');
                }else if(isset($request->sort_by) && $request->sort_by == "date_old_to_new"){
                    return $query->orderBy('created_at', 'asc');
                }else if(isset($request->sort_by) && $request->sort_by == "date_new_to_old"){
                    return $query->orderBy('created_at', 'desc');
                }else{
                    return $query->orderBy('created_at', 'desc');
                }
            })->when($nameSearchParam, function ($query, $nameSearchParam) use ($request) {
                return $query->where('name', 'LIKE', '%' . $nameSearchParam . '%');
            })->when($statusSearchParam, function ($query, $statusSearchParam) use ($request) {
                return $query->where('status', $statusSearchParam);
            });

            if(isset($request->export)){
                $records = $records->get();
                return Excel::download(new PlanReportExport($records), 'planreportdata.xlsx');
            }else{
                $records = $records->paginate(12);
                return JsonResponser::send(false, 'Record found successfully', $records, 200);
            }

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(!auth()->user()->hasPermission('create.ecommerceplans')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $validate = $this->validatePlan($request);

        if ($validate->fails()) {
            return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all(), 400);
        }

        try {
            //check if category exist
            $planExist = EcommercePlan::where('name', $request->name)->first();

            if(!is_null($planExist)){
                return JsonResponser::send(true, $request->name.' already exist. please try again.', [], 400);
            }

            $currentUserInstance = UserMgtHelper::userInstance();

            DB::beginTransaction();

            $plan = EcommercePlan::create([
                'name' => $request->name,
                'description' => $request->description,
                'interval' => $request->interval,
                'price' => $request->price,
                'created_by' => $currentUserInstance->id,
                'status' => "Active",
                "is_active" => 0,
            ]);


            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $plan->id,
                'action_type' => "Models\EcommercePlan",
                'log_name' => "Ecommerce Plan created Successfully",
                'action' => 'Create',
                'description' => "Ecommerce Plan created Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Ecommerce Plan created Successfully!', $plan, 200);
        } catch (\Throwable $error) {
            DB::rollBack();
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if(!auth()->user()->hasPermission('view.ecommerceplans')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        try {
            $plan = EcommercePlan::find($id);

            if(!$plan){
                return JsonResponser::send(true, 'Record Not Found', null, 404);
            }
            return JsonResponser::send(false, 'Record(s) found successully!', $plan, 200);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage() [], 500);
        }
    }

    /**
     * Validate Plan Request
     */
    public function validatePlan(Request $request)
    {
        if ($request->isMethod('put')) {
            $rules = [

                'name' => 'required|unique:ecommerce_plans,name,' . $request->id . ",id",
                'description' => 'required',
                'interval' => 'required',
                'price' => 'required'
             ];
        } else {
            $rules = [

                'name' => 'required|unique:ecommerce_plans,name',
                'description' => 'required',
                'interval' => 'required',
                'price' => 'required'
            ];
        }

        $validateCategory = Validator::make($request->all(), $rules);
        return $validateCategory;
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
        if(!auth()->user()->hasPermission('edit.ecommerceplans')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $plan = EcommercePlan::find($id);

        if (!$plan) {
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        /**
         * Validate Request
         */
        $validate = $this->validatePlan($request);

        if ($validate->fails()) {
            return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all(), 400);
        }

        $currentUserInstance = UserMgtHelper::userInstance();
        
        try {
            DB::beginTransaction();
            $isActive = ($request->status == "Active") ? 1 : 0;

            $plan->update([
                'name' => $request->name,
                'description' => $request->description,
                'interval' => $request->interval,
                'price' => $request->price,
                'status' => $request->status,
                'is_active' => $isActive
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $plan->id,
                'action_type' => "Models\EcommercePlan",
                'log_name' => "EcommercePlan updated Successfully",
                'action' => 'Update',
                'description' => "EcommercePlan updated Successfully  by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Record Updated Successfully!', $plan, 200);
        } catch (\Throwable $error) {
            DB::rollBack();
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

}