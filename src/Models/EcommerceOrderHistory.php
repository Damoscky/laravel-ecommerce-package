<?php

namespace SbscPackage\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcommerceOrderHistory extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function ecommerceorderdetail()
    {
        return $this->belongsTo(EcommerceOrderDetails::class, 'ecommerce_order_detail_id');
    }
}
