<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Telco extends Model
{
    protected $table = 'telcos';
    protected $fillable = [
        'name',
    ];

    public function operators()
    {
        return $this->hasMany(Operator::class, 'id_telco');
    }

    public function statusTelcos()
    {
        return $this->hasMany(StatusTelco::class, 'id_telco');
    }
}
