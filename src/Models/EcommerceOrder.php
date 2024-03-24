<?php

namespace SbscPackage\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use SbscPackage\Ecommerce\Services\Transactions\Transactions;

class EcommerceOrder extends Model
{
    use HasFactory;

    // protected $with = ['order'];

    protected $guarded = ['id']; 
    
    protected $with = ['ecommerceproduct'];

    public function ecommerceorderdetails()
    {
        return $this->hasMany(EcommerceOrderDetails::class, 'ecommerce_order_id');
    }

    public function ecommerceproduct()
    {
        return $this->belongsTo(EcommerceProduct::class, 'ecommerce_product_id');
    }

    public function ecommercehistories()
    {
        return $this->hasMany(EcommerceOrderHistory::class, 'ecommerce_order_detail_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ecommercetransaction()
    {
        return $this->hasOne(EcommerceTransaction::class, 'order_id');
    }
    
     public function ecommerceshippingaddress()
    {
        return $this->hasOne(EcommerceShippingAddress::class);
    }

    public function ecommercebillingdetails()
    {
        return $this->hasOne(EcommerceBillingDetails::class);
    }

    public function getUserDetailsAttribute()
    {
        $user = $this->user;
        return [
            "customer_name" => isset($user) ? $user->firstname.' '.$user->lastname : "",
            "customer_email" => isset($user) ? $user->email : "",
        ];
    }
}
