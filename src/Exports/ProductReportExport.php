<?php

namespace SbscPackage\Ecommerce\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;
use Auth, Date;
use Carbon\Carbon;

class ProductReportExport implements FromCollection, WithHeadings, WithMapping
{

    protected $products;

    public function __construct($products)
    {
        $this->products = $products;
    }


    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $products = $this->products;
    }

    public function map($products): array
    {
        return [
            $products->id,
            $products->product_name,
            isset($products->category) ? $products->category->name : "N/A",
            isset($products->subcategory) ? $products->subcategory->name : "N/A",
            $products->short_description,
            $products->long_description,
            $products->sku,
            $products->tags,
            $products->regular_price,
            $products->sales_price,
            $products->weight_type,
            $products->height,
            $products->length,
            $products->quantity_supplied,
            $products->quantity_purchased,
            $products->quantity_supplied - $products->quantity_purchased,
            $products->in_stock ? 'True' : 'Flase',
            $products->status,
            Carbon::parse($products->created_at),
        ];
    }

    public function headings(): array
    {
        return array('ID', 'Name', 'Category', 'Sub Category', 'Short Description', 'Long Description', 'Sku',
         'Tags', 'Regular Price', 'Sales price', 'Weight type', 'Height', 'Length', 'Qty Supplied', 'Qty Purchased', 'Qty Available', 'In Stock', 'Status', 'Date Created');
    }
}
