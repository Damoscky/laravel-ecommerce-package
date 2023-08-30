<?php

namespace SbscPackage\Ecommerce\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;
use Auth, Date;
use Carbon\Carbon;

class ComplaintReportExport implements FromCollection, WithHeadings, WithMapping
{

    protected $records;

    public function __construct($records)
    {
        $this->records = $records;
    }


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
            $records->id,
            $records->customer->firstname. ' '.$records->customer->lastname,
            $records->customer->email,
            $records->customer->phoneno,
            $records->reason,
            $records->comment,
            Carbon::parse($records->created_at),
        ];
    }

    public function headings(): array
    {
        return array('ID','Customer Name', 'Email', 'Phoneno', 'Reason', 'Comment', 'Date Created');
    }
}
