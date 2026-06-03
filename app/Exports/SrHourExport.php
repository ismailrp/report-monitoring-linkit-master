<?php

namespace App\Exports;

use App\Models\SrHour;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SrHourExport implements FromCollection, WithHeadings, WithMapping
{
    protected $startDate;
    protected $endDate;
    protected $countryId;
    protected $operatorId;
    protected $serviceId;

    public function __construct($startDate = null, $endDate = null, $countryId = null, $operatorId = null, $serviceId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->countryId = $countryId;
        $this->operatorId = $operatorId;
        $this->serviceId = $serviceId;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $query = SrHour::query()->with(['operator', 'service']);

        if ($this->startDate && $this->endDate) {
             $query->whereBetween('date', [$this->startDate, $this->endDate]);
        }

        if ($this->countryId) {
            $country = \App\Models\Country::find($this->countryId);
            if ($country) {
                $query->where('country', $country->country);
            }
        }
        if ($this->operatorId) {
            $query->where('id_operator', $this->operatorId);
        }
        if ($this->serviceId) {
            $query->where('id_service', $this->serviceId);
        }

        return $query->latest('date')->orderBy('hour')->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Hour',
            'Country',
            'Operator',
            'Service',
            'Total Click',
            'Total MO',
            'SR (%)',
        ];
    }

    public function map($row): array
    {
        return [
            $row->date,
            $row->hour,
            $row->country,
            $row->operator ? $row->operator->operator_name : $row->id_operator,
            $row->service ? $row->service->service_name : $row->id_service,
            $row->total_click,
            $row->total_mo,
            $row->sr,
        ];
    }
}
