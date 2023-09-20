<?php

namespace SbscPackage\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EcommerceProduct extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id'];
    protected $with = ['category', 'subcategory', 'ecommerceVendor'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    public function ecommerceorderdetails()
    {
        return $this->hasMany(EcommerceOrderDetails::class);
    }

    public function ecommerceproductsubscription()
    {
        return $this->hasMany(EcommerceProductSubscription::class);
    }

    public function ecommerceVendor()
    {
        return $this->belongsTo(EcommerceVendor::class, 'ecommerce_vendor_id');
    }

}
