<?php

namespace SbscPackage\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class EcommerceCart extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $with = ['ecommerceproduct', 'user'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ecommerceproduct()
    {
        return $this->belongsTo(EcommerceProduct::class, 'ecommerce_product_id');
    }
}
