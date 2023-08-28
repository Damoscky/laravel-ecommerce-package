<?php

namespace SbscPackage\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function subcategory()
    {
        return $this->hasMany(SubCategory::class, 'category_id');
    }

    public function product()
    {
        return $this->hasMany(EcommerceProduct::class);
    }

}
