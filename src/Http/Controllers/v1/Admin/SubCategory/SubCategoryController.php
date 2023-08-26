<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\SubCategory;

use Illuminate\Routing\Controller as BaseController;
use SbscPackage\Ecommerce\Models\SubCategory;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades;
use Illuminate\Support\Carbon;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Responser\JsonResponser;;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class SubCategoryController extends BaseController
{
    public function index(Request $request)
    {
        $currentUser = \Session::get('user');

        $subcategorySearchParam = $request->subcategory_name;
        $statusSearchParam = $request->status;

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        if(!isset($request->sort)){
            $sort = 'ASC';
        }else if($request->sort == 'asc'){
            $sort = 'ASC';
        }else if($request->sort == 'desc'){
            $sort = 'DESC';
        }

        try {
            $records = SubCategory::orderBy('created_at', $sort)
            ->when($subcategorySearchParam, function($query, $subcategorySearchParam) use($request) {
                return $query->where('name', 'LIKE', '%' .$subcategorySearchParam. '%');
            })->when($statusSearchParam, function($query, $statusSearchParam) use($request) {
                return $query->where('status', $statusSearchParam);
            })->paginate(10);

            if(!$records){
                return JsonResponser::send(true, "Record not found.", [], 400);
            }

            return JsonResponser::send(false, 'Record found successfully', $records, 200);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function getSubCategoryNoPagination()
    {
        try {
            $records = SubCategory::orderBy('name', 'ASC')->get();

            return JsonResponser::send(false, $records->count() . ' Record(s) Available', $records, 200);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
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
        $validate = $this->validateSubcategory($request);

        if ($validate->fails()) {
            return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all());
        }

        //check if sub category exist
        $subCategoryExist = SubCategory::where('name', $request->subcategory_name)->first();
        if(!is_null($subCategoryExist)){
            return JsonResponser::send(true, $request->subcategory_name.' already exist, please try again.', []);
        }

        $currentUserInstance = \Session::get('user');

        if(isset($request->file)){
            $file = $request->file;
            $imageInfo = explode(';base64,', $file);
            $checkExtention = explode('data:', $imageInfo[0]);
            $checkExtention = explode('/', $checkExtention[1]);
            $fileExt = str_replace(' ', '', $checkExtention[1]);
            $image = str_replace(' ', '+', $imageInfo[1]);
            $uniqueId = Str::slug($request->subcategory_name);
            $name = 'category_' . $uniqueId . '.' . $fileExt;
            $fileUrl = config('app.url') . 'category/' . $name;
            Storage::disk('category')->put($name, base64_decode($image));
           
        }else{
            $fileUrl = null;
        }

        try {
            $subCategory = SubCategory::create([
                'category_id' => $request->category_id,
                'name' => $request->subcategory_name,
                "slug" => Str::slug($request->subcategory_name),
                "file_path" => $fileUrl,
                'created_by' => $currentUserInstance['id'],
            ]);


            $dataToLog = [
                'causer_id' => $currentUserInstance['id'],
                'action_id' => $subCategory->id,
                'action_type' => "Models\SubCategory",
                'log_name' => "SubCategory created Successfully",
                'description' => "SubCategory created Successfully by {$currentUserInstance['lastname']} {$currentUserInstance['firstname']}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            return JsonResponser::send(false, 'Subcategory created Successfully!', $subCategory);
        } catch (\Throwable $error) {
            logger($th);
            return JsonResponser::send(true, $error->getMessage(), null, 500);
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
        $subcategory = SubCategory::find($id);

        if (!$subcategory) {
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        return JsonResponser::send(false, '', $subcategory);
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
        $subcategory = SubCategory::find($id);

        if (!$subcategory) {
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        $validate = $this->validateSubcategory($request);

        if ($validate->fails()) {
            return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all(), 400);
        }
        
        $subcatNameExist = SubCategory::where('id', '!=', $id)->where('name', $request->subcategory_name)->where('category_id', $request->cat_id)->first();

        if (!is_null($subcatNameExist)) {
            return JsonResponser::send(true, 'Division name already exist', [], 400);
        }

        if(isset($request->file)){
            $file = $request->file;
            $imageInfo = explode(';base64,', $file);
            $checkExtention = explode('data:', $imageInfo[0]);
            $checkExtention = explode('/', $checkExtention[1]);
            $fileExt = str_replace(' ', '', $checkExtention[1]);
            $image = str_replace(' ', '+', $imageInfo[1]);
            $uniqueId = Str::slug($request->subcategory_name);
            $name = 'category_' . $uniqueId . '.' . $fileExt;
            $fileUrl = config('app.url') . 'category/' . $name;
            Storage::disk('category')->put($name, base64_decode($image));
           
        }else{
            $fileUrl = $subcategory->file_path;
        }
                
        try {

            $currentUserInstance = \Session::get('user');

            $subcategory->update([
                'category_id' => $request->category_id,
                'name' => $request->subcategory_name,
                "file_path" => $fileUrl,
                "slug" => Str::slug($request->subcategory_name)
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstance['id'],
                'action_id' => $subcategory->id,
                'action_type' => "Models\SubCategory",
                'log_name' => "SubCategory updated Successfully",
                'description' => "SubCategory updated Successfully by {$currentUserInstance['lastname']} {$currentUserInstance['firstname']}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            return JsonResponser::send(false, 'SubCategory Updated Successfully!', $subcategory);
        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, 'Internal server error', null, 500);
        }
    }


    /**
     * Validate Subcategory Request
     */
    public function validateSubcategory(Request $request)
    {
        if ($request->isMethod('put')) {
            $rules = [
                'category_id' => 'required',
            ];
        } else {
            $rules = [
                'category_id' => 'required',
                'subcategory_name' => 'required|unique:sub_categories,name'
            ];
        }

        $validateSubcategory = Validator::make($request->all(), $rules);
        return $validateSubcategory;
    }

    /**
     * Activate Sub Category
     */
    public function activate($id)
    {
        $subCategory = SubCategory::find($id);

        if (!$subCategory) {
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        try {
            $subCategory->update([
                'is_active' => 1,
                'status' => "Active",
            ]);

            $currentUserInstance = \Session::get('user');

            $dataToLog = [
                'causer_id' => $currentUserInstance['id'],
                'action_id' => $subCategory->id,
                'action_type' => "Models\SubCategory",
                'log_name' => "SubCategory activated Successfully",
                'description' => "SubCategory activated Successfully by {$currentUserInstance['lastname']} {$currentUserInstance['firstname']}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            return JsonResponser::send(false, 'Subcategory Activated Successfully!', $subCategory);
        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, 'Internal server error', null, 500);
        }
    }

    /**
     * Deactivate Sub Category
     */
    public function deactivate($id)
    {
        $subCategory = SubCategory::find($id);

        if (!$subCategory) {
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        try {
            $subCategory->update([
                'is_active' => 0,
                'status' => "Inactive",
            ]);

            $currentUserInstance =  \Session::get('user');

            $dataToLog = [
                'causer_id' => $currentUserInstance['id'],
                'action_id' => $subCategory->id,
                'action_type' => "Models\SubCategory",
                'log_name' => "SubCategory deactivated Successfully",
                'description' => "SubCategory deactivated Successfully by {$currentUserInstance['lastname']} {$currentUserInstance['firstname']}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            return JsonResponser::send(false, 'Subcategory dectivated Successfully!', $subCategory);
        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, $th->getMessage(), null, 500);
        }
    }
}
