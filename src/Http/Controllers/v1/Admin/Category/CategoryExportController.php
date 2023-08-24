<?php

namespace App\Http\Controllers\v1\Admin\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CategoriesReportExport;
use App\Models\Category;
use App\Responser\JsonResponser;
use Illuminate\Support\Carbon;

class CategoryExportController extends Controller
{
    public function exportCategories(Request $request)
    {
        $categorySearchParam = $request->category;
        $statusSearchParam = $request->status;
        $sort = $request->sort;

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
                    return $query->where('cat_name', 'LIKE', '%' .$categorySearchParam. '%');
                })->when($statusSearchParam, function($query, $statusSearchParam) use($request) {
                    return $query->where('is_active', $statusSearchParam);
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
