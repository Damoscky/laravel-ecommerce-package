<?php

namespace SbscPackage\Authentication\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function causer()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }
    
}
