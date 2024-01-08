<?php

namespace SbscPackage\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $guarded = ["id"];

    public function regions()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function states()
    {
        return $this->hasMany(State::class);
    }
}