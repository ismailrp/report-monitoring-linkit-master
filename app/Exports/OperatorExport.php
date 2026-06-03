<?php

namespace App\Exports;

use App\Models\Operator;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OperatorExport implements FromCollection, WithHeadings, WithMapping
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
        $query = Operator::query()->with('country');

        if ($this->search) {
             $query->where('operator_name', 'like', '%' . $this->search . '%')
                  ->orWhere('operator_code', 'like', '%' . $this->search . '%');
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Operator Name',
            'Operator Code',
            'Status',
            'Country',
        ];
    }

    public function map($operator): array
    {
        return [
            $operator->id,
            $operator->operator_name,
            $operator->operator_code,
            $operator->status,
            $operator->country ? $operator->country->country : $operator->country_id,
        ];
    }
}
