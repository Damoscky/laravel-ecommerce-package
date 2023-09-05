<?php

namespace SbscPackage\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class EcommerceVendor extends Model
{
    use HasFactory;
     use SoftDeletes;

    protected $guarded = ['id'];

    public function ecommerceProducts()
    {
        return $this->hasMany(EcommerceProduct::class, 'ecommerce_vendor_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
