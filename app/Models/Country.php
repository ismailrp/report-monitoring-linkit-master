<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    /** @use HasFactory<\Database\Factories\CountryFactory> */
    use HasFactory;

    protected $table = 'countries';
    protected $guarded = [];
    public $timestamps = false;

     public function operators()
     {
         return $this->hasMany(Operator::class, 'id_country');
     }
     public function alerts()
     {
         return $this->hasMany(Alert::class, 'id_country');
     }

     public function services()
     {
         return $this->hasMany(Service::class, 'id_country');
     }

     public function summariesDaily()
     {
         return $this->hasMany(SummaryDaily::class, 'country_id');
     }
}
