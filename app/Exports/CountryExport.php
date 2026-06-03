<?php

namespace App\Exports;

use App\Models\Country;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CountryExport implements FromCollection, WithHeadings, WithMapping
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
        $query = Country::query();

        if ($this->search) {
             $query->where('country', 'like', '%' . $this->search . '%')
                  ->orWhere('country_code', 'like', '%' . $this->search . '%');
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Country',
            'Code',
            'Currency',
            'Chat ID',
        ];
    }

    public function map($country): array
    {
        return [
            $country->id,
            $country->country,
            $country->country_code,
            $country->currency,
            $country->chat_id,
        ];
    }
}
