<?php

namespace App\Exports;

use App\Models\SummaryDaily;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SummaryDailyExport implements FromCollection, WithHeadings, WithMapping
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
        $query = SummaryDaily::query()->with(['country', 'operator', 'service']);

        if ($this->startDate && $this->endDate) {
             $query->whereBetween('date', [$this->startDate, $this->endDate]);
        }
        
        if ($this->countryId) {
            $query->where('id_country', $this->countryId);
        }
        if ($this->operatorId) {
            $query->where('id_operator', $this->operatorId);
        }
        if ($this->serviceId) {
            $query->where('id_service', $this->serviceId);
        }

        // Apply User Scope: If not admin/superuser, restrict to assigned operators
        $user = auth()->user();
        if ($user && !$user->hasRole(['super_admin', 'admin'])) {
            $operatorIds = $user->operators()->pluck('operators.id');
            $query->whereIn('id_operator', $operatorIds);
        }

        return $query->latest('date')->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Country',
            'Operator',
            'Service',
            'MO Reg',
            'MO Unreg',
            'Click',
            'MT Success',
            'MT Failed',
            'MT Retry',
            'MT Retry Success',
            'Revenue',
            'Revenue USD',
            'Sub Active',
            'SR',
            'Quality Index',
        ];
    }

    public function map($row): array
    {
        return [
            $row->date ? $row->date->format('Y-m-d') : '',
            $row->country ? $row->country->country : $row->id_country,
            $row->operator ? $row->operator->operator : $row->id_operator,
            $row->service ? $row->service->service : $row->id_service,
            $row->mo_reg,
            $row->mo_unreg,
            $row->click,
            $row->mt_success,
            $row->mt_failed,
            $row->mt_retry,
            $row->mt_retry_success,
            $row->revenue,
            $row->revenue_usd,
            $row->sub_active,
            $row->sr,
            $row->quality_index . '%',
        ];
    }
}

