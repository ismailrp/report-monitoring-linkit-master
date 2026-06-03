<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SummaryDaily extends Model
{
    /** @use HasFactory<\Database\Factories\SummaryDailyFactory> */
    use HasFactory;
    protected $table = 'summary_daily';

    protected $fillable = [
        'date',
        'hour',
        'id_service',
        'id_operator',
        'id_country',
        'mo_reg',
        'mo_unreg',
        'click',
        'mt_success',
        'mt_failed',
        'mt_retry_success',
        'mt_retry',
        'revenue',
        'sub_active',
        'sr',
    ];
    protected $casts = [
        'date' => 'date',
    ];

    public function getRevenueUsdAttribute()
    {
        $rate = $this->country?->convert_usd ?? 0;
        if (strtoupper($this->country?->country ?? '') === 'OMAN') {
            $rate = $rate / 1000;
        }
        return round($this->revenue * $rate, 2);
    }

    public function getQualityIndexAttribute()
    {
        if ($this->sub_active > 0) {
            return round(($this->mo_unreg / $this->sub_active) * 100, 2);
        }
        return 0;
    }

    public  function country()
    {
        return $this->belongsTo(Country::class, 'id_country');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'id_service');
    }
    public function operator()
    {
        return $this->belongsTo(Operator::class, 'id_operator');
    }

}
