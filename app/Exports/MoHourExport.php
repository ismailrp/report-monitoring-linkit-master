<?php

namespace App\Exports;

use App\Models\MoHour;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MoHourExport implements FromCollection, WithHeadings, WithMapping
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $query = MoHour::query()->with(['operator', 'service']);

        if ($this->startDate && $this->endDate) {
             $query->whereBetween('date', [$this->startDate, $this->endDate]);
        }

        return $query->latest('date')->orderBy('hour')->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Hour',
            'Operator',
            'Service',
            'Total Reg',
            'Total Unreg',
        ];
    }

    public function map($row): array
    {
        return [
            $row->date,
            $row->hour,
            $row->operator ? $row->operator->operator_name : $row->id_operator,
            $row->service ? $row->service->service_name : $row->id_service,
            $row->total_reg,
            $row->total_unreg,
        ];
    }
}
