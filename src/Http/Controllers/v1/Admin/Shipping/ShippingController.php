<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Shipping;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Helpers\UserMgtHelper;
use SbscPackage\Ecommerce\Models\ShippingSettings;
use SbscPackage\Ecommerce\Models\ShippingZone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ShippingController extends BaseController
{ 
    /**
     * Shipping Zone
     */

    public function shippingZone(Request $request)
    {
        $contentInfoSearchParam = $request->name;
        $zoneSearchParam = $request->zone;
        $sort = $request->sort;
        
        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;
        
        if(!isset($request->sort)){
            $sort = 'DESC';
        }else if($request->sort == 'asc'){
            $sort = 'ASC';
        }else if($request->sort == 'desc'){
            $sort = 'DESC';
        }

        try {
            $record = ShippingZone::with('countries', 'zones', 'zones.regions', 'zones.regions.countries', 'zones.regions.countries.states')->orderBy('created_at', $sort)
                ->when($contentInfoSearchParam, function($query, $contentInfoSearchParam) use($request) {
                    return $query->where('name', 'LIKE', '%' .$contentInfoSearchParam. '%');
                })->when($zoneSearchParam, function($query, $zoneSearchParam) use($request) {
                    return $query->whereHas('zones', function($query) use ($zoneSearchParam){
                        return $query->where('name', 'LIKE', '%' .$zoneSearchParam. '%');
                    });
                    
                })
                ->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
                    $startDate = Carbon::parse($request->start_date);
                    $endDate = Carbon::parse($request->end_date);
                    return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                })->paginate(12);

            if(!$record){
                return JsonResponser::send(true, "Record not found.", [], 400);
            }

            return JsonResponser::send(false, "Record found successfully", $record, 200);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function createShippingZone(Request $request)
    {
        $currentUserInstance = UserMgtHelper::userInstance();
        $userId = $currentUserInstance->id;

        /**
         * Validate Request
        */
        $validate = $this->validateShippingZone($request);
        
        /**
         * if validation fails
        */
        if ($validate->fails()) {
            return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all());
        }

        try {
            DB::beginTransaction();

        
            $record = ShippingZone::create([
                'name' => $request->name,
                'zone_id' => $request->zone_id,
                'region_id' => $request->region_id,
                'country_id' => $request->country_id,
                'state_id' => $request->state_id,
                'state_name' => $request->state_name,
                'price' => $request->price,
                'is_active' => true,
            ]);

            $dataToLog = [
                'causer_id' => $userId,
                'action_id' => $record->id,
                'action_type' => "Models\ShippingZone",
                'log_name' => "Shipping Zone was created successfully",
                'action' => 'Create',
                'description' => "Shipping Zone was created successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            DB::commit();
            return JsonResponser::send(false, "Record created successfully", $record, 201);
            
        } catch (\Throwable $error) {
            DB::rollback();
            return JsonResponser::send(true, $error->getMessage(), null, 500);
        }
    }

    public function showShippingZone($id)
    {
        try {
            $record = ShippingZone::where('id', $id)->first();
            if(is_null($record)){
                return JsonResponser::send(true, "Record not found", null, 400);
            }

            return JsonResponser::send(false, "Record(s) found successfully", $record, 200);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, "Internal server error", null, 500);
        }
    }

    public function updateShippingZone(Request $request, $id)
    {
        $currentUserInstance = UserMgtHelper::userInstance();
        $userId = $currentUserInstance->id;

        /**
         * Validate Request
        */
        $validate = $this->validateShippingZone($request);
        
        /**
         * if validation fails
        */
        if ($validate->fails()) {
            return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all());
        }

        try {
            DB::beginTransaction();

            //check if record exist
            $record = ShippingZone::where('id', $id)->first();
           
            $updateRecord = $record->update([
                'name' => $request->name,
                'zone_id' => $request->zone_id,
                'region_id' => $request->region_id,
                'country_id' => $request->country_id,
                'state_id' => $request->state_id,
                'state_name' => $request->state_name,
                'price' => $request->price,
                'is_active' => true,
            ]);

            $dataToLog = [
                'causer_id' => $userId,
                'action_id' => $record->id,
                'action_type' => "Models\ShippingZone",
                'log_name' => "Shipping Zone was updated successfully",
                'action' => 'Update',
                'description' => "Shipping Zone was updated successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            DB::commit();
            return JsonResponser::send(false, "Record updated successfully", $updateRecord, 200);
          
        } catch (\Throwable $error) {
            DB::rollback();
            return JsonResponser::send(true, "Internal server error", null, 500);
        }
    }
 
    public function deleteShippingZone($id)
    {
        try {
            DB::beginTransaction();

            $record = ShippingZone::where('id', $id)->first();
            if(is_null($record)){
                return JsonResponser::send(true, 'Record not found', [], 400);
            }

            $user = auth()->user();

            $record->delete();

            $dataToLog = [
                'causer_id' => $user->id,
                'action_id' => $record->id,
                'action_type' => "Models\ShippingZone",
                'log_name' => "Shipping Zone deleted successfully",
                'action' => 'Delete',
                'description' => "{$user->firstname} {$user->lastname} deleted Shipping Zone successfully",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, "Record deleted successfully", [], 200);
        } catch (\Throwable $error) {
            DB::rollBack();
            logger($error);
            return JsonResponser::send(true, "Internal server error", null, 500);
        }
    }

    public function activateShippingZone($id)
    {
        $record = ShippingZone::where('id', $id)->first();

        if(!$record){
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        $currentUserInstance = auth()->user();
        try {
            DB::beginTransaction();

            $record->update([
                'is_active' => 1,
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $record->id,
                'action_type' => "Models\ShippingZone",
                'log_name' => "Shipping Zone activated Successfully",
                'action' => 'Update',
                'description' => "Shipping Zone activated successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Shipping Zone Activated Successfully!', $record, 200);
        } catch (\Throwable $error) {
            DB::rollBack();
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    public function deactivateShippingZone($id)
    {
        $record = ShippingZone::where('id', $id)->first();

        if(!$record){
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        $currentUserInstance = auth()->user();
        try {
            DB::beginTransaction();

            $record->update([
                'is_active' => 0,
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $record->id,
                'action_type' => "Models\ShippingZone",
                'log_name' => "Shipping Zone deactivated successfully",
                'action' => 'Update',
                'description' => "Shipping Zone deactivated successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Shipping Zone Deactivated Successfully!', $record, 200);
        } catch (\Throwable $error) {
            DB::rollBack();
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    protected function validateShippingZone(Request $request)
    {
        $rules = [
            'name' => "required",
            'zone_id' => "nullable",
            'region_id' => "nullable",
            'country_id' => "required",
            'state_id' => "required",
            'state_name' => "required",
            'price' => "required",
        ];

        $validateRequest = Validator::make($request->all(), $rules);
        return $validateRequest;
    }
}
