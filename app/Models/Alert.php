<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $table = 'alerts';
    protected $guarded = [];

    public function operator()
    {
       return $this->belongsTo(Operator::class,'id_operator');
    }

    public function service()
    {
        return $this->belongsTo(Service::class,'id_service');
    }

    public function country()
    {
        return $this->belongsTo(Country::class,'id_country');
    }
}
