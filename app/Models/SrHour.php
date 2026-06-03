<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SrHour extends Model
{
    /** @use HasFactory<\Database\Factories\SrHourFactory> */
    use HasFactory;
    protected $table = 'sr_hours';
//     date
// hour
// country
// id_operator
// operator
// id_service
// service
// total_click
// total_mo
// sr
// id_country
    protected $fillable = [
        'date',
        'hour',
        'country',
        'id_operator',
        'operator',
        'id_service',
        'service',
        'total_click',
        'total_mo',
        'sr',
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
