<?php

namespace SbscPackage\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcommerceShippingAddress extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function ecommerceorder()
    {
        return $this->belongsTo(EcommerceOrder::class);
    }
}
