<?php

namespace SbscPackage\Ecommerce\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;
use Auth;
use Carbon\Carbon;

class RecurringOrderExportReport implements FromCollection, WithHeadings, WithMapping
{

    protected $records;

    public function __construct($records)
    {
        $this->records = $records;
    }


    /**
     * @return \Illuminate\Support\Collection
     */
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $records = $this->records;
    }


    public function map($records): array
    {
        return [
            isset($records->user) ? $records->user->firstname.' '.$records->user->lastname : '',
            isset($records->user) ? $records->user->email : '',
            $records->interval,
            Carbon::parse($records->start_date)->toDayDateTimeString(),
            Carbon::parse($records->end_date)->toDayDateTimeString(),
            isset($records->ecommerceproduct) ? $records->ecommerceproduct->product_name : '',
            isset($records->ecommerceorderdetails) ? $records->ecommerceorderdetails->unit_price : '0.00',
            $records->quantity,
            $records->quantity * $records->ecommerceorderdetails->unit_price,
            Carbon::parse($records->created_at)->toDayDateTimeString(),
        ];
    }

    public function headings(): array
    {
        return array('Customer Name', 'Customer Email', 'Interval', 'Start Date', 'End Date',
        'Product Name', 'Price', 'Quantity', 'Total Price', 'Created at');
    }
}
