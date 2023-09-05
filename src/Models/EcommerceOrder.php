<?php

namespace SbscPackage\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class EcommerceOrder extends Model
{
    use HasFactory;
    protected $guarded = ["id"];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ecommerceorderdetails()
    {
        return $this->hasMany(EcommerceOrderDetails::class);
    }

    public function ecommerceshippingaddress()
    {
        return $this->hasOne(EcommerceShippingAddress::class);
    }

    public function ecommercebillingdetails()
    {
        return $this->hasOne(EcommerceBillingDetails::class);
    }

    // public function transactions()
    // {
    //     return $this->belongsTo(Transaction::class, "transaction_id");
    // }

}
