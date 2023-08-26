<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Category;

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
use Session;

class CategoryController extends BaseController
{
    public function index(Request $request)
    {
        // $currentUser = \Session::get('user');

        $categorySearchParam = $request->category_name;
        $statusSearchParam = $request->status;
        
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

    
        if(!isset($request->sort)){
            $sort = 'ASC';
        }else if($request->sort == 'asc'){
            $sort = 'ASC';
        }else if($request->sort == 'desc'){
            $sort = 'DESC';
        }

        try {
            $records = Category::with('subcategory', 'product')
            ->when($categorySearchParam, function($query, $categorySearchParam) use($request) {
                return $query->where('name', 'LIKE', '%' .$categorySearchParam. '%');
            })->when($statusSearchParam, function($query, $statusSearchParam) use($request) {
                return $query->where('status', $statusSearchParam);
            })
            ->when($sortByRequestParam, function ($query) use ($request) {
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
                return Excel::download(new CategoriesReportExport($records), 'categoriesreportdata.xlsx');
            }else{
                $records = $records->paginate(12);
                return JsonResponser::send(false, 'Record found successfully', $records, 200);
            }

        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function getCategoryNoPagination()
    {
        try {
            $categories = Category::where('status', "Active")->orderBy('name', 'ASC')->get();

            return JsonResponser::send(false, $categories->count() . ' Categor(ies) Available', $categories, 200);
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
        $validate = $this->validateCategory($request);

        if ($validate->fails()) {
            return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all(), 400);
        }

        try {
            //check if category exist
            $categoryExist = Category::where('name', $request->category_name)->first();

            if(!is_null($categoryExist)){
                return JsonResponser::send(true, $request->category_name.' already exist. please try again.', [], 400);
            }

            $currentUserInstance = UserMgtHelper::userInstance();

            DB::beginTransaction();

            if(isset($request->file)){
                $file = $request->file;
                $imageInfo = explode(';base64,', $file);
                $checkExtention = explode('data:', $imageInfo[0]);
                $checkExtention = explode('/', $checkExtention[1]);
                $fileExt = str_replace(' ', '', $checkExtention[1]);
                $image = str_replace(' ', '+', $imageInfo[1]);
                $uniqueId = Str::slug($request->category_name);
                $name = 'category_' . $uniqueId . '.' . $fileExt;
                $fileUrl = config('app.url') . 'assets/category/' . $name;
                Storage::disk('category')->put($name, base64_decode($image));
               
            }else{
                $fileUrl = null;
            }

            $category = Category::create([
                'name' => $request->category_name,
                'slug' => Str::slug($request->category_name),
                'created_by' => $currentUserInstance->id,
                'status' => "Pending Approval",
                "is_active" => 0,
                'file_path' => $fileUrl
            ]);


            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $category->id,
                'action_type' => "Models\Category",
                'log_name' => "Category created Successfully",
                'description' => "Category created Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Category created Successfully!', $category, 200);
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
        try {
            $category = Category::find($id);

            if(!$category){
                return JsonResponser::send(true, 'Record Not Found', null, 404);
            }
            return JsonResponser::send(false, 'Record(s) found successully!', $category, 200);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, 'Internal server error', [], 500);
        }
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
        $category = Category::find($id);

        if (!$category) {
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        /**
         * Validate Request
         */
        $validate = $this->validateCategory($request);

        if ($validate->fails()) {
            return JsonResponser::send(true, $validate->errors()->first(), $validate->errors()->all(), 400);
        }

        if(isset($request->file)){
            $file = $request->file;
            $imageInfo = explode(';base64,', $file);
            $checkExtention = explode('data:', $imageInfo[0]);
            $checkExtention = explode('/', $checkExtention[1]);
            $fileExt = str_replace(' ', '', $checkExtention[1]);
            $image = str_replace(' ', '+', $imageInfo[1]);
            $uniqueId = Str::slug($request->category_name);
            $name = 'category_' . $uniqueId . '.' . $fileExt;
            $fileUrl = config('app.url') . 'assets/category/' . $name;
            Storage::disk('category')->put($name, base64_decode($image));
        }else{
            $fileUrl = $category->file_path;
        }

        $currentUserInstance = UserMgtHelper::userInstance();
        
        try {
            DB::beginTransaction();

            $category->update([
                "name" => $request->category_name,
                "slug" => Str::slug($request->category_name),
                "status" => "Pending Approval",
                "is_active" => 0, 
                "file_path" => $fileUrl,
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $category->id,
                'action_type' => "Models\Category",
                'log_name' => "Category updated Successfully",
                'description' => "Category updated Successfully  by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Record Updated Successfully!', $category, 200);
        } catch (\Throwable $error) {
            DB::rollBack();
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    /**
     * Validate Category Request
     */
    public function validateCategory(Request $request)
    {
        if ($request->isMethod('put')) {
            $rules = [

                'category_name' => 'required|unique:categories,name,' . $request->id . ",id",
            ];
        } else {
            $rules = [

                'category_name' => 'required|unique:categories,name'
            ];
        }

        $validateCategory = Validator::make($request->all(), $rules);
        return $validateCategory;
    }

    /**
     * Activate Category
     */
    public function activate($id)
    {
        $category = Category::find($id);

        if(!$category){
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        $currentUserInstance = UserMgtHelper::userInstance();
        try {
            DB::beginTransaction();

            $category->update([
                'is_active' => 1,
                "status" => "Active"
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $category->id,
                'action_type' => "Models\Category",
                'log_name' => "Category activated Successfully",
                'description' => "Category activated Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Category Activated Successfully!', $category, 200);
        } catch (\Throwable $error) {
            DB::rollBack();
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    /**
     * Approve Deleted Category
     */
    public function approveDeletedCategory($id)
    {
        $category = Category::find($id);

        if(!$category){
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        $currentUserInstance = UserMgtHelper::userInstance();
        try {
            DB::beginTransaction();

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $category->id,
                'action_type' => "Models\Category",
                'log_name' => "Category deleted Successfully",
                'description' => "Category deleted Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            $category->delete();

            DB::commit();
            return JsonResponser::send(false, 'Category Deleted Successfully!', $category, 200);
        } catch (\Throwable $error) {
            DB::rollBack();
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    /**
     * Deactivate Category
     */
    public function deactivate($id)
    {
        $category = Category::find($id);

        if(!$category){
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        $currentUserInstance = UserMgtHelper::userInstance();

        try {
            DB::beginTransaction();

            $category->update([
                'is_active' => 0,
                "status" => "Inactive"
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $category->id,
                'action_type' => "Models\Category",
                'log_name' => "Category deactivated Successfully",
                'description' => "Category deactivated Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Category Deactivated Successfully!', $category, 200);
        } catch (\Throwable $error) {
            DB::rollBack();
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    /**
     * Delete Category
     */
    public function deleteCategory($id)
    {
        $category = Category::find($id);

        if(!$category){
            return JsonResponser::send(true, 'Record Not Found', null, 404);
        }

        $currentUserInstance = UserMgtHelper::userInstance();

        try {
            DB::beginTransaction();

            $category->update([
                "status" => "Pending Delete"
            ]);

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $category->id,
                'action_type' => "Models\Category",
                'log_name' => "Category deleted Successfully",
                'description' => "Category deleted Successfully by {$currentUserInstance->lastname} {$currentUserInstance->firstname}",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);
            DB::commit();
            return JsonResponser::send(false, 'Category Send for approval Successfully!', $category, 200);
        } catch (\Throwable $error) {
            DB::rollBack();
            logger($error);
            return JsonResponser::send(true, 'Internal server error!', [], 500);
        }
    }

    public function exportCategories(Request $request)
    {
        $categorySearchParam = $request->category_name;
        $statusSearchParam = $request->status;
        $sort = $request->sort;
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        if(!isset($request->sort)){
            $sort = 'ASC';
        }else if($request->sort == 'asc'){
            $sort = 'ASC';
        }else if($request->sort == 'desc'){
            $sort = 'DESC';
        }

        try {
            $categories = Category::orderBy('created_at', $sort)
                ->when($categorySearchParam, function($query, $categorySearchParam) use($request) {
                    return $query->where('name', 'LIKE', '%' .$categorySearchParam. '%');
                })->when($statusSearchParam, function($query, $statusSearchParam) use($request) {
                    return $query->where('is_active', $statusSearchParam);
                })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
                    $startDate = Carbon::parse($request->start_date);
                    $endDate = Carbon::parse($request->end_date);
                    return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                })->when($alphabetically, function ($query) {
                    return $query->orderBy('name', 'asc');
                })
                ->when($dateOldToNew, function ($query) {
                    return $query->orderBy('created_at', 'asc');
                })
                ->when($dateNewToOld, function ($query) {
                    return $query->orderBy('created_at', 'desc');
                })->paginate(12);


            if(!$categories){
                return JsonResponser::send(true, "Record not found.", [], 400);
            }

            return Excel::download(new CategoriesReportExport($categories), 'categoriesreportdata.xlsx');

            return JsonResponser::send(false, $categories->count() . ' Categor(ies) Available', $categories);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function pendingCategory(Request $request)
    {
        $categorySearchParam = $request->category_name;
        $statusSearchParam = $request->status;
        $sort = $request->sort;
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        if(!isset($request->sort)){
            $sort = 'ASC';
        }else if($request->sort == 'asc'){
            $sort = 'ASC';
        }else if($request->sort == 'desc'){
            $sort = 'DESC';
        }

        try {
            $categories = Category::orderBy('created_at', $sort)
                ->when($categorySearchParam, function($query, $categorySearchParam) use($request) {
                    return $query->where('name', 'LIKE', '%' .$categorySearchParam. '%');
                })->when($statusSearchParam, function($query, $statusSearchParam) use($request) {
                    return $query->where('is_active', $statusSearchParam);
                })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
                    $startDate = Carbon::parse($request->start_date);
                    $endDate = Carbon::parse($request->end_date);
                    return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                })->when($sortByRequestParam, function ($query) use ($request) {
                    if(isset($request->sort_by) && $request->sort_by == "alphabetically"){
                        return $query->orderBy('name', 'asc');
                    }else if(isset($request->sort_by) && $request->sort_by == "date_old_to_new"){
                        return $query->orderBy('created_at', 'asc');
                    }else if(isset($request->sort_by) && $request->sort_by == "date_new_to_old"){
                        return $query->orderBy('created_at', 'desc');
                    }
                })->where('status', "Pending Approval")->paginate(12);

            return JsonResponser::send(false, $categories->count() . ' Categor(ies) Available', $categories);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function pendingDeletedCategory(Request $request)
    {
        $categorySearchParam = $request->category_name;
        $statusSearchParam = $request->status;
        $sort = $request->sort;
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        if(!isset($request->sort)){
            $sort = 'ASC';
        }else if($request->sort == 'asc'){
            $sort = 'ASC';
        }else if($request->sort == 'desc'){
            $sort = 'DESC';
        }

        try {
            $categories = Category::orderBy('created_at', $sort)
                ->when($categorySearchParam, function($query, $categorySearchParam) use($request) {
                    return $query->where('name', 'LIKE', '%' .$categorySearchParam. '%');
                })->when($statusSearchParam, function($query, $statusSearchParam) use($request) {
                    return $query->where('is_active', $statusSearchParam);
                })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
                    $startDate = Carbon::parse($request->start_date);
                    $endDate = Carbon::parse($request->end_date);
                    return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                })->when($sortByRequestParam, function ($query) use ($request) {
                    if(isset($request->sort_by) && $request->sort_by == "alphabetically"){
                        return $query->orderBy('name', 'asc');
                    }else if(isset($request->sort_by) && $request->sort_by == "date_old_to_new"){
                        return $query->orderBy('created_at', 'asc');
                    }else if(isset($request->sort_by) && $request->sort_by == "date_new_to_old"){
                        return $query->orderBy('created_at', 'desc');
                    }
                })->where('status', "Pending Delete")->paginate(12);

            return JsonResponser::send(false, $categories->count() . ' Categor(ies) Available', $categories);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }


    public function approvedCategory(Request $request)
    {
        $categorySearchParam = $request->category_name;
        $statusSearchParam = $request->status;
        $sort = $request->sort;
        $sortByRequestParam = $request->sort_by;

        (!is_null($request->start_date) && !is_null($request->end_date)) ? $dateSearchParams = true : $dateSearchParams = false;

        if(!isset($request->sort)){
            $sort = 'ASC';
        }else if($request->sort == 'asc'){
            $sort = 'ASC';
        }else if($request->sort == 'desc'){
            $sort = 'DESC';
        }

        try {
            $categories = Category::orderBy('created_at', $sort)
                ->when($categorySearchParam, function($query, $categorySearchParam) use($request) {
                    return $query->where('name', 'LIKE', '%' .$categorySearchParam. '%');
                })->when($statusSearchParam, function($query, $statusSearchParam) use($request) {
                    return $query->where('is_active', $statusSearchParam);
                })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
                    $startDate = Carbon::parse($request->start_date);
                    $endDate = Carbon::parse($request->end_date);
                    return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                })->when($sortByRequestParam, function ($query) use ($request) {
                    if(isset($request->sort_by) && $request->sort_by == "alphabetically"){
                        return $query->orderBy('name', 'asc');
                    }else if(isset($request->sort_by) && $request->sort_by == "date_old_to_new"){
                        return $query->orderBy('created_at', 'asc');
                    }else if(isset($request->sort_by) && $request->sort_by == "date_new_to_old"){
                        return $query->orderBy('created_at', 'desc');
                    }
                })->where('status', "Approved")->paginate(12);

            return JsonResponser::send(false, $categories->count() . ' Categor(ies) Available', $categories);
        } catch (\Throwable $error) {
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    
}
