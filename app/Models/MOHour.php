<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MOHour extends Model
{
    /** @use HasFactory<\Database\Factories\MoHourFactory> */
    use HasFactory;

    protected $table = 'mo_hours';

    public $timestamps = false;
}
