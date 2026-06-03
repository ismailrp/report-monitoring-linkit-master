<?php

namespace App\Exports;

use App\Models\Merchant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MerchantExport implements FromCollection, WithHeadings, WithMapping
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
        $query = Merchant::query();

        if ($this->search) {
             $query->where('merchant_name', 'like', '%' . $this->search . '%')
                  ->orWhere('merchant_code', 'like', '%' . $this->search . '%');
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Merchant Name',
            'Merchant Code',
            'Password',
            'Country ID',
        ];
    }

    public function map($merchant): array
    {
        return [
            $merchant->id,
            $merchant->merchant_name,
            $merchant->merchant_code,
            $merchant->password,
            $merchant->country_id,
        ];
    }
}
