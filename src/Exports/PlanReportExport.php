<?php

namespace SbscPackage\Ecommerce\Exports;


use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Carbon;

class PlanReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $plan;

    public function __construct($plan)
    {
        $this->plan = $plan;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // return Category::all();

        return $plan = $this->plan;
    }

    public function map($plan): array
    {
        return [
            $plan->id,
            $plan->name,
            $plan->description,
            $plan->is_active ? 'Active' : 'Inactive',
            Carbon::parse($plan->created_at),
        ];
    }

    public function headings(): array
    {
        return array('ID', 'Name', 'Description', 'Status', 'Created At');
    }
}
