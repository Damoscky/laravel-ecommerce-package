<?php

namespace SbscPackage\Ecommerce\Http\Controllers\v1\Admin\Category;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use SbscPackage\Ecommerce\Exports\CategoriesReportExport;
use SbscPackage\Ecommerce\Models\Category;
use SbscPackage\Ecommerce\Responser\JsonResponser;
use Illuminate\Support\Carbon;

class CategoryExportController extends BaseController
{
    public function exportCategories(Request $request)
    {
        $categorySearchParam = $request->category;
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
            $categories = Category::when($categorySearchParam, function($query, $categorySearchParam) use($request) {
                    return $query->where('cat_name', 'LIKE', '%' .$categorySearchParam. '%');
                })->when($statusSearchParam, function($query, $statusSearchParam) use($request) {
                    return $query->where('is_active', $statusSearchParam);
                })->when($sortByRequestParam, function ($query) use ($request) {
                    if(isset($request->sort_by) && $request->sort_by == "alphabetically"){
                        return $query->orderBy('name', 'asc');
                    }else if(isset($request->sort_by) && $request->sort_by == "date_old_to_new"){
                        return $query->orderBy('created_at', 'asc');
                    }else if(isset($request->sort_by) && $request->sort_by == "date_new_to_old"){
                        return $query->orderBy('created_at', 'desc');
                    }
                })->when($dateSearchParams, function($query, $dateSearchParams) use($request) {
                    $startDate = Carbon::parse($request->start_date);
                    $endDate = Carbon::parse($request->end_date);
                    return $query->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
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
}
