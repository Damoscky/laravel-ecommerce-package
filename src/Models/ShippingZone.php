<?php

namespace SbscPackage\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'state_id' => 'array',
        'state_name' => 'array',
    ];

    public function zones()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function regions()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function countries()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function states()
    {
        return $this->belongsTo(State::class, 'state_id');
    }
}
