<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceFactory> */
    use HasFactory;
    protected $table = 'services';
    protected $fillable = [
        'service',
        'id_operator',
        'id_country',
        'id_merchant',
        'sdc',
        'price',
        'type',
        'is_active',
        'name_wakiad',
    ];

    public function Operator()
    {
        return $this->belongsTo(Operator::class,'id_operator');
    }

    public function sumWeekly()
    {
        return $this->hasMany(SummaryWeekly::class, 'id_service');
    }

    public function summariesDaily()
    {
        return $this->hasMany(SummaryDaily::class, 'id_service');
    }
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'id_merchant');
    }
}
