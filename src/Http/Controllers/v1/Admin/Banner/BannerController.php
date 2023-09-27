<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Banner;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Helpers\UserMgtHelper;
use SbscPackage\Ecommerce\Models\Category;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use Illuminate\Support\Facades\DB;
use SbscPackage\Ecommerce\Exports\CategoriesReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use SbscPackage\Ecommerce\Helpers\FileUploadHelper;
use SbscPackage\Ecommerce\Models\EcommerceBanner;
use Session;

class BannerController extends BaseController
{

    public function index()
    {
        $banner = EcommerceBanner::get();
        return JsonResponser::send(false, "Record found succesfully", $banner);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(!auth()->user()->hasPermission('create.ecommercebanner')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $validate = $this->validateBannerRequest($request);

        if ($validate->fails()) {
            return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all());
        }

        DB::beginTransaction();

        $currentUserInstance = UserMgtHelper::userInstance();

        //check if banner exist
        $bannerExists = EcommerceBanner::where('position', $request->position)->first();
        

        if(is_null($bannerExists)){

            if (isset($request->file)) {
                $bannerImage = $request->file;
                $imageKey = 'EcommerceBanner';
                $fileUrl = FileUploadHelper::singleStringFileUpload($bannerImage, $imageKey);
            } else {
                $fileUrl = null;
            }
            
            $banner = EcommerceBanner::create([
                'position' => $request->position,
                'file_path' => $fileUrl,
                'created_by' => $currentUserInstance->id
            ]);
            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $banner->id,
                'action_type' => "Models\EcommerceBanner",
                'log_name' => "EcommerceBanner created Successfully",
                'action' => 'Create',
                'description' => "EcommerceBanner created Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];
    
            
        }else{

            if (isset($request->file)) {
                $bannerImage = $request->file;
                $imageKey = 'EcommerceBanner';
                $fileUrl = FileUploadHelper::singleStringFileUpload($bannerImage, $imageKey);
            } else {
                $fileUrl = $bannerExists->file_path;
            }
            $bannerExists->update([
                'file_path' => $fileUrl,
                'created_by' => $currentUserInstance->id
            ]);
            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $bannerExists->id,
                'action_type' => "Models\EcommerceBanner",
                'log_name' => "EcommerceBanner created Successfully",
                'action' => 'Create',
                'description' => "EcommerceBanner updated Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];
    
        }
        
        ProcessAuditLog::storeAuditLog($dataToLog);
        DB::commit();

        return JsonResponser::send(false, "Record created succesfully", []);

    }

    /**
     * Validate Subcategory Request
     */
    public function validateBannerRequest(Request $request)
    {
        $rules = [
            'position' => 'required',
            'file' => 'required'
        ];

        $validate = Validator::make($request->all(), $rules);
        return $validate;
    }
}