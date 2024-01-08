<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\ShippingAndLogistics;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use SbscPackage\Ecommerce\Exports\LogisticsCompanyReportExport;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Helpers\UserMgtHelper;
use SbscPackage\Ecommerce\Models\LogisticsCompany;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LogisticsCompanyController extends BaseController
{
    //
    //index for logistics company
    public function index(Request $request)
    {
        $companyNameSearchParam = $request->company_name;
        $companyAddressSearchParam = $request->company_address;

        $alphabetically = $request->alphabetically;
        $dateOldToNew = $request->date_old_to_new;
        $dateNewToOld = $request->date_new_to_old;
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        if (!isset($request->sort)) {
            $sort = 'ASC';
        } else if ($request->sort == 'asc') {
            $sort = 'ASC';
        } else if ($request->sort == 'desc') {
            $sort = 'DESC';
        }

        try {
            $logisticsCompany = LogisticsCompany::when($companyNameSearchParam, function ($query, $companyNameSearchParam) {
                return $query->where('company_name', 'like', '%' . $companyNameSearchParam . '%');
            })
            ->when($companyAddressSearchParam, function ($query, $companyAddressSearchParam) {
                return $query->where('company_address', 'like', '%' . $companyAddressSearchParam . '%');
            })
            // ->when($dateSearchParams, function ($query, $dateSearchParams) use ($request) {
            //     $startDate = Carbon::parse($request->product_start_date);
            //     $endDate = Carbon::parse($request->product_end_date);
            //     return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
            // })
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

            ->when($alphabetically, function ($query) {
                return $query->orderBy('company_name', 'asc');
            })
            ->when($dateOldToNew, function ($query) {
                return $query->orderBy('created_at', 'asc');
            })
            ->when($dateNewToOld, function ($query) {
                return $query->orderBy('created_at', 'desc');
            })
            ->orderBy('created_at', $sort)
            ->paginate(10);
            if (!$logisticsCompany) {
                return JsonResponser::send(true, 'No record(s) found!', [], 400);
            }

            return JsonResponser::send(false, 'Record(s) found successfully', $logisticsCompany, 200);
        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    public function getAllLogisticsCompany()
    {
        $logisticsCompany = LogisticsCompany::all();
        return JsonResponser::send(false, 'Record(s) found successfully', $logisticsCompany, 200);

    }

    public function exportLogisticsCompany(Request $request)
    {
        $companyNameSearchParam = $request->company_name;
        $companyAddressSearchParam = $request->company_address;

        $alphabetically = $request->alphabetically;
        $dateOldToNew = $request->date_old_to_new;
        $dateNewToOld = $request->date_new_to_old;

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        if (!isset($request->sort)) {
            $sort = 'ASC';
        } else if ($request->sort == 'asc') {
            $sort = 'ASC';
        } else if ($request->sort == 'desc') {
            $sort = 'DESC';
        }

        try {
            $logisticsCompany = LogisticsCompany::when($companyNameSearchParam, function ($query, $companyNameSearchParam) {
                return $query->where('company_name', 'like', '%' . $companyNameSearchParam . '%');
            })
                ->when($companyAddressSearchParam, function ($query, $companyAddressSearchParam) {
                    return $query->where('company_address', 'like', '%' . $companyAddressSearchParam . '%');
                })
                // ->when($dateSearchParams, function ($query, $dateSearchParams) use ($request) {
                //     $startDate = Carbon::parse($request->product_start_date);
                //     $endDate = Carbon::parse($request->product_end_date);
                //     return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                // })
                ->when($alphabetically, function ($query) {
                    return $query->orderBy('company_name', 'asc');
                })
                ->when($dateOldToNew, function ($query) {
                    return $query->orderBy('created_at', 'asc');
                })
                ->when($dateNewToOld, function ($query) {
                    return $query->orderBy('created_at', 'desc');
                })
                ->orderBy('created_at', $sort)
                ->paginate(12);
            if (!$logisticsCompany) {
                return JsonResponser::send(true, 'No record(s) found!', [], 400);
            }
            return Excel::download(new LogisticsCompanyReportExport($logisticsCompany), 'logisticscompanyreportdata.xlsx');

            return JsonResponser::send(false, $logisticsCompany->count() . ' Company(s) Available', $logisticsCompany);
        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    public function validateLogisticsCompany(Request $request)
    {
        $rules = [
            'company_name' => 'required|string|min:3|max:250',
            'company_email' => 'required',
            'company_address' => 'required|string|min:3|max:250',
            'contact_firstname' => 'required|string|min:3|max:250',
            'contact_lastname' => 'required|string|min:3|max:250',
            'contact_number1' => 'required',
            'contact_number2' => 'required',
            'driver_information1' => 'nullable',
            'driver_information2' => 'nullable',
        ];

        $validateLogisticsCompany = Validator::make($request->all(), $rules);
        return $validateLogisticsCompany;
    }

    public function create(Request $request)
    {
        $validate = $this->validateLogisticsCompany($request);
        /**
         * if validation fails
         */
        if ($validate->fails()) {
            return JsonResponser::send(true, 'Validation Failed', $validate->errors()->all());
        }

        $userInstance = UserMgtHelper::userInstance();
        $userId = $userInstance->id;

        try {
            DB::beginTransaction();

            $logisticsCompany = LogisticsCompany::create([
                'company_name' => $request->company_name,
                'company_email' => $request->company_email,
                'company_address' => $request->company_address,
                'contact_firstname' => $request->contact_firstname,
                'contact_lastname' => $request->contact_lastname,
                'contact_number1' => $request->contact_number1,
                'contact_number2' => $request->contact_number2,
                'driver_information1' => $request->driver_information1,
                'driver_information2' => $request->driver_information2
            ]);

            $currentUserInstance = auth()->user();

            $dataToLog = [
                'causer_id' => auth()->user()->id,
                'action_id' => $logisticsCompany->id,
                'action_type' => "Models\LogisticsCompany",
                'log_name' => "Logistics company created Successfully",
                'action' => 'Create',
                'description' => "Logistics company created Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Logistics Company Saved Successfully!', $logisticsCompany);
        } catch (\Exception $th) {
            DB::rollBack();
            logger($th);
            return JsonResponser::send(true, 'Internal Server error', [], 500);
        }
    }

    public function show($id)
    {
        try {
            $logisticsCompany = LogisticsCompany::where('id', $id)->first();

            if (!$logisticsCompany) {
                return JsonResponser::send(true, "Record not found.", [], 400);
            }

            return JsonResponser::send(false, 'Logistics Company Found', $logisticsCompany, 200);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $logisticsCompany = LogisticsCompany::find($id);

        if (!$logisticsCompany) {
            return JsonResponser::send(true, 'Logistics Company Not Found', []);
        }

        $userInstance = UserMgtHelper::userInstance();
        $userId = $userInstance->id;

        /**
         * Validate Request
         */
        $validate = $this->validateLogisticsCompany($request);
        /**
         * if validation fails
         */
        if ($validate->fails()) {
            return JsonResponser::send(true, 'Validation failed', $validate->errors()->all());
        }


        try {
            DB::beginTransaction();

            $logisticsCompany->update([
                'company_name' => $request->company_name,
                'company_email' => $request->company_email,
                'company_address' => $request->company_address,
                'contact_firstname' => $request->contact_firstname,
                'contact_lastname' => $request->contact_lastname,
                'contact_number1' => $request->contact_number1,
                'contact_number2' => $request->contact_number2,
                'driver_information1' => $request->driver_information1,
                'driver_information2' => $request->driver_information2
            ]);


            $currentUserInstance = auth()->user();

            $dataToLog = [
                'causer_id' => auth()->user()->id,
                'action_id' => $logisticsCompany->id,
                'action_type' => "Models\LogisticsCompany",
                'log_name' => "Logistics company created Successfully",
                'action' => 'Create',
                'description' => "Logistics company created Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Logistics Company Updated Successfully!', $logisticsCompany);
        } catch (\Throwable $th) {
            logger($th);
            DB::rollBack();
            return JsonResponser::send(true, 'Internal Server error', [], 500);
        }
    }

    public function updateLogisticsCompanyStatus(Request $request, $id)
    {
        $logisticsCompany = LogisticsCompany::find($id);

        if (!$logisticsCompany) {
            return JsonResponser::send(true, 'Logistics company not found', null);
        }

        try {
            DB::beginTransaction();

            $logisticsCompany->update([
                'is_active' => $request->is_active
            ]);

            $currentUserInstance = auth()->user();

            $dataToLog = [
                'causer_id' => auth()->user()->id,
                'action_id' => $logisticsCompany->id,
                'action_type' => "Models\logisticsCompany",
                'log_name' => "Logistics company status activated Successfully",
                'action' => 'Update',
                'description' => "Logistics company activated Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Status Updated Successfully!', $logisticsCompany);
        } catch (\Throwable $th) {
            logger($th);
            DB::rollBack();
            return JsonResponser::send(true, 'Internal Server error', [], 500);
        }
    }
}
