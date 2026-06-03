<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SummaryStatus extends Model
{
    protected $table = 'summary_status';

    protected $fillable = [
        'date',
        'hour',
        'id_service',
        'id_operator',
        'id_country',
        'status',
        'total'
    ];
    protected $casts = [
        'date' => 'date',
    ];

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
