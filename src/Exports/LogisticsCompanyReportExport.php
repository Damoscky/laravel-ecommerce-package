<?php

namespace SbscPackage\Ecommerce\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;
use Auth;
use Carbon\Carbon;

class LogisticsCompanyReportExport implements FromCollection, WithHeadings, WithMapping
{

    protected $logisticsCompany;

    public function __construct($logisticsCompany)
    {
        $this->logisticsCompany = $logisticsCompany;
    }


    /**
     * @return \Illuminate\Support\Collection
     */
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $logisticsCompany = $this->logisticsCompany;
    }

    public function map($logisticsCompany): array
    {
        return [
            $logisticsCompany->id,
            $logisticsCompany->company_name,
            $logisticsCompany->company_email,
            $logisticsCompany->company_address,
            $logisticsCompany->contact_number1,
            $logisticsCompany->contact_number2,
            Carbon::parse($logisticsCompany->created_at),
        ];
    }

    public function headings(): array
    {
        return array('ID', 'Company Name', 'Company Email', 'Company Address', 'Phone Number 1', 'Phone Number 2', 'Date Created');
    }
}
