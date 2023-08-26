<?php

namespace SbscPackage\Ecommerce\Exports;

use SbscPackage\Ecommerce\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Carbon;

class SubCategoriesReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $subcategories;

    public function __construct($subcategories)
    {
        $this->subcategories = $subcategories;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // return Category::all();

        return $subcategories = $this->subcategories;
    }

    public function map($subcategories): array
    {
        return [
            $subcategories->id,
            $subcategories->name,
            $subcategories->category->name,
            $subcategories->file_path,
            $subcategories->is_active ? 'Active' : 'Inactive',
            Carbon::parse($subcategories->created_at),
        ];
    }

    public function headings(): array
    {
        return array('ID', 'Name', 'Category Name', 'Logo', 'Status', 'Created At');
    }
}
