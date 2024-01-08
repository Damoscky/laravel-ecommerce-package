<?php

namespace SbscPackage\Ecommerce\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;
use Auth, Date;
use Carbon\Carbon;

class ShippingOrderReportExport implements FromCollection, WithHeadings, WithMapping
{

    protected $orders;

    public function __construct($orders)
    {
        $this->orders = $orders;
    }


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
            $orders->id,
            $orders->orderNO,
            $orders->product_name,
            $orders->unit_price,
            $orders->shipping_fee,
            $orders->quantity_ordered,
            isset($orders->user) ? $orders->user->firstname . ' ' . $orders->user->lastname : '',
            isset($orders->user) ? $orders->user->email : '',
            Carbon::parse($orders->created_at),
            isset($orders->user->usershipping) ? $orders->user->usershipping->address : '',
            $orders->status,
            isset($orders->logisticsCompany) ? $orders->logisticsCompany->company_name : '',
            isset($orders->logisticsCompany) ? $orders->logisticsCompany->contact_firstname . '' . $orders->logisticsCompany->contact_lastname : '',

        ];
    }

    public function headings(): array
    {
        return array('ID', 'Order No', 'Product Name', 'Unit Price', 'Shipping Fee', 'Quantity', 'Customer Name', 'Customer Email', 'Timestamp', 'Delivery Address', 'Order Status', 'Logistics Company', 'Contact Person Name');
    }
}
