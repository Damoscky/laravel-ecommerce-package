<?php

namespace SbscPackage\Ecommerce\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;
use Auth;
use Carbon\Carbon;

class OrderExportReport implements FromCollection, WithHeadings, WithMapping
{

    protected $orders;

    public function __construct($orders)
    {
        $this->orders = $orders;
    }


    /**
     * @return \Illuminate\Support\Collection
     */
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $orders = $this->orders;
    }


    public function map($orders): array
    {
        return [
            $orders->orderID,
            $orders->fullname,
            $orders->email,
            $orders->status,
            $orders->total_price,
            $orders->payment_status,
            $orders->payment_method,
            Carbon::parse($orders->created_at)->toDayDateTimeString(),
        ];
    }

    public function headings(): array
    {
        return array('Order ID', 'Customer Name', 'Customer Email', 'Order Status', 'Total Price', 
        'Payment Status', 'Payment Method', 'Created at');
    }
}
