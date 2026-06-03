<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SummaryWeekly extends Model
{
    /** @use HasFactory<\Database\Factories\SummaryWeeklyFactory> */
    use HasFactory;
    protected $table = 'summary_weekly';
    protected $fillable = [ 
        'year',
        'periode',
        'start_date',
        'end_date',
        'id_country',
        'id_operator',
        'total_mt',
        'total_mo',
        'total_revenue',
        'total_sub_active',
    ];  
    protected $casts = [
        'date' => 'date',
    ];  

    public function operator()
    {
        return $this->belongsTo(Operator::class,'id_operator');
    }
    public function country()
    {
        return $this->belongsTo(Country::class,'id_country');
    }

    public function getRevenueUsdAttribute()
    {
        $rate = $this->country?->convert_usd ?? 0;
        if (strtoupper($this->country?->country ?? '') === 'OMAN') {
            $rate = $rate / 1000;
        }
        return round($this->total_revenue * $rate, 2);
    }
}
