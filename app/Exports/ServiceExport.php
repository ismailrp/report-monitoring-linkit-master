<?php

namespace App\Exports;

use App\Models\Service;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ServiceExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $query = Service::query()->with('Operator');

        if ($this->search) {
             $query->where('service_name', 'like', '%' . $this->search . '%')
                  ->orWhere('service_code', 'like', '%' . $this->search . '%');
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Service Name',
            'Service Code',
            'Operator',
        ];
    }

    public function map($service): array
    {
        return [
            $service->id,
            $service->service_name,
            $service->service_code,
            $service->Operator ? $service->Operator->operator_name : $service->id_operator,
        ];
    }
}
