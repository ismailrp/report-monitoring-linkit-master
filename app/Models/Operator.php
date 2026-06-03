<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    /** @use HasFactory<\Database\Factories\OperatorFactory> */
    use HasFactory;
    protected $table = 'operators';
    protected $fillable = [
        'operator',
        'id_country',
        'alias',
        'id_telco',
    ];  

    public function telco()
    {
        return $this->belongsTo(Telco::class, 'id_telco');
    }

    public function Services()
    {
        return $this->hasMany(Service::class, 'id_operator');
    }
    public function alerts()
    {
        return $this->hasMany(Alert::class,'id_operator');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'id_country');
    }

    public function sumWeekly()
    {
        return $this->hasMany(SummaryWeekly::class, 'id_operator');
    }

    public function sumDaily()
    {
        return $this->hasMany(SummaryDaily::class, 'operator_id');
    }

}
