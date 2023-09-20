<?php

namespace SbscPackage\Ecommerce\Exports;

use SbscPackage\Ecommerce\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Carbon;

class CategoriesReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $categories;

    public function __construct($categories)
    {
        $this->categories = $categories;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // return Category::all();

        return $categories = $this->categories;
    }

    public function map($categories): array
    {
        return [
            $categories->id,
            $categories->name,
            $categories->file_path,
            $categories->is_active ? 'Active' : 'Inactive',
            Carbon::parse($categories->created_at),
        ];
    }

    public function headings(): array
    {
        return array('ID', 'Category Name', 'Logo', 'Status', 'Date Created');
    }
}
