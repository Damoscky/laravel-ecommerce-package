<?php

namespace SbscPackage\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    protected $guarded = ["id"];

    public function countries()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
