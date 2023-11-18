<?php

namespace SbscPackage\Ecommerce\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;
use Auth, Date;
use Carbon\Carbon;

class NewsLetterReportExport implements FromCollection, WithHeadings, WithMapping
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
            $records->email,
            Carbon::parse($records->created_at),
        ];
    }

    public function headings(): array
    {
        return array('Email', 'Date Subscribed');
    }
}
