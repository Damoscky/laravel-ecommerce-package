<?php

namespace SbscPackage\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class EcommerceComplaint extends Model
{
    use HasFactory;
    protected $guarded = ["id"];

    protected $with = ['customer', 'ecommerceorderdetails.ecommerceproduct', 'complaintstatus'];

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ecommerceorderdetails()
    {
        return $this->belongsTo(EcommerceOrderDetails::class, 'ecommerce_order_details_id');
    }

    public function complaintstatus()
    {
        return $this->hasMany(EcommerceComplaintStatus::class, "ecommerce_complaint_id");
    }

}
