<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\SubCategory;

use Illuminate\Routing\Controller as BaseController;
use SbscPackage\Ecommerce\Models\SubCategory;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades;
use Illuminate\Support\Carbon;
use DB;
use SbscPackage\Ecommerce\Helpers\ProcessAuditLog;
use SbscPackage\Ecommerce\Helpers\UserMgtHelper;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use Illuminate\Support\Facades\Validator;
use SbscPackage\Ecommerce\Exports\SubCategoriesReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use SbscPackage\Ecommerce\Helpers\FileUploadHelper;

class SubCategoryController extends BaseController
{
    public function index(Request $request)
    {
        if(!auth()->user()->hasPermission('view.subcategory')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        $currentUserInstance = UserMgtHelper::userInstance();

        $subcategorySearchParam = $request->subcategory_name;
        $statusSearchParam = $request->status;
        $sortByRequestParam = $request->sort_by;
        $categorySearchParam = $request->category_id;

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        if(!isset($request->sort)){
            $sort = 'ASC';
        }else if($request->sort == 'asc'){
            $sort = 'ASC';
        }else if($request->sort == 'desc'){
            $sort = 'DESC';
        }

        try {
            $records = SubCategory::with('category', 'product')->when($subcategorySearchParam, function($query, $subcategorySearchParam) use($request) {
                return $query->where('name', 'LIKE', '%' .$subcategorySearchParam. '%');
            })->when($categorySearchParam, function ($query, $categorySearchParam) use ($request) {
                return $query->whereHas('category', function ($query) use ($categorySearchParam) {
                    return $query->where('id', $categorySearchParam);
                });
            })->when($statusSearchParam, function($query, $statusSearchParam) use($request) {
                return $query->where('status', $statusSearchParam);
            })->when($sortByRequestParam, function ($query) use ($request) {
                if(isset($request->sort_by) && $request->sort_by == "alphabetically"){
                    return $query->orderBy('name', 'asc');
                }else if(isset($request->sort_by) && $request->sort_by == "date_old_to_new"){
                    return $query->orderBy('created_at', 'asc');
                }else if(isset($request->sort_by) && $request->sort_by == "date_new_to_old"){
                    return $query->orderBy('created_at', 'desc');
                }
            });

            if(isset($request->export)){
                $records = $records->get();
                return Excel::download(new SubCategoriesReportExport($records), 'subcategoriesreportdata.xlsx');
            }else{
                $records = $records->paginate(12);
                return JsonResponser::send(false, 'Record found successfully', $records, 200);
            }

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function getSubCategoryNoPagination()
    {
        try {
            $subcategories = SubCategory::where('status', "Active")->orderBy('name', 'ASC')->get();

            return JsonResponser::send(false, $subcategories->count() . ' Subcategor(ies) Available', $subcategories, 200);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function getSubCategoryByCategoryId($id)
    {
        try {
            $subcategories = SubCategory::where('category_id', $id)->where('status', "Active")->orderBy('name', 'ASC')->get();

            return JsonResponser::send(false, 'Record found successfully', $subcategories, 200);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    /**
     * Delete Category
     */
    public function deleteSubCategory($id)
    {
        if(!auth()->user()->hasPermission('delete.subcategory')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        $subcategory = SubCategory::find($id);

        if(!$subcategory){
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        $currentUserInstance = UserMgtHelper::userInstance();

        try {
            DB::beginTransaction();

            $subcategory->update([
                "status" => "Pending Delete"
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $subcategory->id,
                'action_type' => "Models\SubCategory",
                'log_name' => "SubCategory Pending Delete Successfully",
                'description' => "SubCategory Pending Delete Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Sub Category Send for approval Successfully!', $subcategory, 200);
        } catch (\Throwable $error) {
            DB::rollBack();
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    public function pendingSubcategory(Request $request)
    {
        if(!auth()->user()->hasPermission('view.subcategory')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        $subcategorySearchParam = $request->subcategory_name;
        $statusSearchParam = $request->status;
        $sortByRequestParam = $request->sort_by;
        $categorySearchParam = $request->category_id;

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        if(!isset($request->sort)){
            $sort = 'ASC';
        }else if($request->sort == 'asc'){
            $sort = 'ASC';
        }else if($request->sort == 'desc'){
            $sort = 'DESC';
        }

        try {
            $records = SubCategory::with('category', 'product')->when($subcategorySearchParam, function($query, $subcategorySearchParam) use($request) {
                return $query->where('name', 'LIKE', '%' .$subcategorySearchParam. '%');
            })->when($categorySearchParam, function ($query, $categorySearchParam) use ($request) {
                return $query->whereHas('category', function ($query) use ($categorySearchParam) {
                    return $query->where('id', $categorySearchParam);
                });
            })->when($statusSearchParam, function($query, $statusSearchParam) use($request) {
                return $query->where('status', $statusSearchParam);
            })->when($sortByRequestParam, function ($query) use ($request) {
                if(isset($request->sort_by) && $request->sort_by == "alphabetically"){
                    return $query->orderBy('name', 'asc');
                }else if(isset($request->sort_by) && $request->sort_by == "date_old_to_new"){
                    return $query->orderBy('created_at', 'asc');
                }else if(isset($request->sort_by) && $request->sort_by == "date_new_to_old"){
                    return $query->orderBy('created_at', 'desc');
                }
            })->where('status', 'Pending Approval')->paginate(12);

            return JsonResponser::send(false, 'Record found successfully', $records, 200);

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
        if(!auth()->user()->hasPermission('create.subcategory')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }

        $validate = $this->validateSubcategory($request);

        if ($validate->fails()) {
            return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all());
        }

        //check if sub category exist
        $subCategoryExist = SubCategory::where('name', $request->subcategory_name)->first();
        if(!is_null($subCategoryExist)){
            return JsonResponser::send(true, $request->subcategory_name.' already exist, please try again.', []);
        }

        DB::beginTransaction();

        $currentUserInstance = UserMgtHelper::userInstance();

        if (isset($request->file)) {
            $categoryImage = $request->file;
            $imageKey = 'Category';
            $fileUrl = FileUploadHelper::singleStringFileUpload($categoryImage, $imageKey);
        } else {
            $fileUrl = null;
        }

        try {
            $subCategory = SubCategory::create([
                'category_id' => $request->category_id,
                'name' => $request->subcategory_name,
                "slug" => Str::slug($request->subcategory_name),
                "file_path" => $fileUrl,
                'status' => "Pending Approval",
                "is_active" => 0,
                'created_by' => $currentUserInstance->id,
            ]);


            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $subCategory->id,
                'action_type' => "Models\SubCategory",
                'log_name' => "SubCategory created Successfully",
                'description' => "SubCategory created Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();

            return JsonResponser::send(false, 'Subcategory created Successfully!', $subCategory);
        } catch (\Throwable $error) {
            logger($error);
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
        if(!auth()->user()->hasPermission('view.subcategory')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
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
        if(!auth()->user()->hasPermission('edit.subcategory')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
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

        if (isset($request->file)) {
            $categoryImage = $request->file;
            $imageKey = 'Category';
            $fileUrl = FileUploadHelper::singleStringFileUpload($categoryImage, $imageKey);
        } else {
            $fileUrl = $subcategory->file_path;
        }
                
        try {

            $currentUserInstance = UserMgtHelper::userInstance();

            $subcategory->update([
                'category_id' => $request->category_id,
                'name' => $request->subcategory_name,
                "file_path" => $fileUrl,
                "slug" => Str::slug($request->subcategory_name),
                'status' => "Pending Approval",
                "is_active" => 0,
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $subcategory->id,
                'action_type' => "Models\SubCategory",
                'log_name' => "SubCategory updated Successfully",
                'description' => "SubCategory updated Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            return JsonResponser::send(false, 'SubCategory Updated Successfully!', $subcategory);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), null, 500);
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
        if(!auth()->user()->hasPermission('manage.subcategory')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        $subCategory = SubCategory::find($id);

        if (!$subCategory) {
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        try {
            $subCategory->update([
                'is_active' => 1,
                'status' => "Active",
            ]);

            $currentUserInstance = UserMgtHelper::userInstance();

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $subCategory->id,
                'action_type' => "Models\SubCategory",
                'log_name' => "SubCategory activated Successfully",
                'description' => "SubCategory activated Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            return JsonResponser::send(false, 'Subcategory Activated Successfully!', $subCategory);
        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, 'Internal server error', null, 500);
        }
    }

    
    /**
     * Approve Deleted Category
     */
    public function approveDeletedSubcategory($id)
    {
        if(!auth()->user()->hasPermission('manage.subcategory')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        $subCategory = SubCategory::find($id);

        if (!$subCategory) {
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        try {

            $currentUserInstance = UserMgtHelper::userInstance();

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $subCategory->id,
                'action_type' => "Models\SubCategory",
                'log_name' => "SubCategory deleted Successfully",
                'description' => "SubCategory deleted Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            $subCategory->delete();

            return JsonResponser::send(false, 'Subcategory Deleted Successfully!', $subCategory);
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
        if(!auth()->user()->hasPermission('manage.subcategory')){
            return JsonResponser::send(true, "Permission Denied :(", [], 401);
        }
        $subCategory = SubCategory::find($id);

        if (!$subCategory) {
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        try {
            $subCategory->update([
                'is_active' => 0,
                'status' => "Inactive",
            ]);

            $currentUserInstance =  UserMgtHelper::userInstance();

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $subCategory->id,
                'action_type' => "Models\SubCategory",
                'log_name' => "SubCategory deactivated Successfully",
                'description' => "SubCategory deactivated Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            return JsonResponser::send(false, 'Subcategory dectivated Successfully!', $subCategory);
        } catch (\Throwable $th) {
            logger($th);
            return JsonResponser::send(true, $th->getMessage(), null, 500);
        }
    }
}
