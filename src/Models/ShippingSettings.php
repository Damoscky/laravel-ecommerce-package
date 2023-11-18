<?php

namespace SbscPackage\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingSettings extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'base_store' => 'array',
        'selling_location' => 'array',
        'shipping_location' => 'array',
    ];
}
