<?php

namespace SbscPackage\Ecommerce\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;
use Auth, Date;
use Carbon\Carbon;

class CustomerReportExport implements FromCollection, WithHeadings, WithMapping
{

    protected $customers;

    public function __construct($customers)
    {
        $this->customers = $customers;
    }


    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $customers = $this->customers;
    }

    public function map($customers): array
    {
        return [
            $customers->id,
            $customers->firstname. ' '.$customers->lastname,
            $customers->phoneno,
            Carbon::parse($customers->created_at),
        ];
    }

    public function headings(): array
    {
        return array('ID', 'Name', 'Phone', 'Date Created');
    }
}
