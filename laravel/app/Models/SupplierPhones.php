<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierPhones extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'number',
        'supplier_id',
        'is_main',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
