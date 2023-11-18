<?php

namespace SbscPackage\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $guarded = ["id"];

    public function countries()
    {
        return $this->hasMany(Country::class);
    }

    public function zones()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }
}
