<?php

namespace App\Exports;

use App\Models\SummaryWeekly;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SummaryWeeklyExport implements FromCollection, WithHeadings, WithMapping
{
    protected $startDate;
    protected $endDate;
    protected $country;
    protected $operator;

    public function __construct($startDate = null, $endDate = null, $country = null, $operator = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->country = $country;
        $this->operator = $operator;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $query = SummaryWeekly::query()->with(['country', 'operator']);

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('start_date', [$this->startDate, $this->endDate]);
        }

        if ($this->country) {
            $query->where('id_country', $this->country);
        }
        if ($this->operator) {
            $query->where('id_operator', $this->operator);
        }

        // Apply User Scope
        $user = auth()->user();
        if ($user && !$user->hasRole(['super_admin', 'admin'])) {
            $operatorIds = $user->operators()->pluck('operators.id');
            $query->whereIn('id_operator', $operatorIds);
        }

        return $query->orderByDesc('start_date')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Year',
            'Start Date',
            'End Date',
            'Country',
            'Operator',
            'Total MT',
            'Total MO',
            'Total Revenue',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->year,
            $row->start_date ? \Carbon\Carbon::parse($row->start_date)->format('Y-m-d') : null,
            $row->end_date ? \Carbon\Carbon::parse($row->end_date)->format('Y-m-d') : null,
            $row->country ? $row->country->country : $row->id_country,
            $row->operator ? $row->operator->operator : $row->id_operator,
            $row->total_mt,
            $row->total_mo,
            $row->total_revenue,
        ];
    }
}
