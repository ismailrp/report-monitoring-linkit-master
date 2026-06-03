<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubActiveUserHour extends Model
{
    /** @use HasFactory<\Database\Factories\SubActiveUserHourFactory> */
    use HasFactory;
    protected $table = 'sub_active_user_hours';
    protected $fillable = [
        'date',
        'hour',
        'country',
        'id_operator',
        'operator',
        'id_service',
        'service',
        'total_sub',
        'id_country',
    ];
    public $timestamps = false;

    public function operator()
    {
        return $this->belongsTo(Operator::class, 'id_operator');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'id_service');
    }
}
