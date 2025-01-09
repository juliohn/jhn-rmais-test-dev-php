<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'document',
        'document_type',
        'email',
    ];

    public function addresses()
    {
        return $this->hasMany(SupplierAddress::class);
    }

    public function phones()
    {
        return $this->hasMany(SupplierPhones::class);
    }
}
