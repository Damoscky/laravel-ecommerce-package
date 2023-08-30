<?php

namespace SbscPackage\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class EcommerceComplaintStatus extends Model
{
    use HasFactory;
    protected $guarded = ["id"];

    protected $table = 'ecommerce_complaint_status';

    protected $with = ['user'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ecommercecomplaint()
    {
        return $this->belongsTo(EcommerceComplaint::class, 'ecommerce_complaint_id');
    }

    // public function transactions()
    // {
    //     return $this->belongsTo(Transaction::class, "transaction_id");
    // }

}
