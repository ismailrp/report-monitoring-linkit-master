<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusTelco extends Model
{
    protected $table = 'status_telcos';
    protected $fillable = [
        'id_telco',
        'status_code',
        'type',
        'description',
    ];

    public function telco()
    {
        return $this->belongsTo(Telco::class, 'id_telco');
    }
}
