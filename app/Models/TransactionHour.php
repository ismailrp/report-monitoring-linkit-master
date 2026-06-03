<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionHour extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionHourFactory> */
    use HasFactory;
    protected $table = 'transaction_hours';
    public $timestamps = false;
    // protected $guarded=[];

    protected $fillable = [
        'date',
        'hour',
        'country',
        'id_operator',
        'operator',
        'id_service',
        'service',
        'mt_success',
        'mt_failed',
        'total_mt',
        'revenue',
        'mt_retry',
    ];  

//     date
// hour
// country
// id_operator
// operator
// id_service
// service
// mt_success
// mt_failed
// total_mt
// revenue
// mt_retry
    protected $casts = [
        'date' => 'date',
    ];  

    public function operator()
    {
        return $this->belongsTo(Operator::class, 'id_operator');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'id_service');
    }
}
