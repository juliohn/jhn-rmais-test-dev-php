<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierAddress extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'is_main',
        'cep',
        'street',
        'number',
        'complement',
        'city',
        'state',
        'neighborhood',
        'reference',
        'supplier_id'
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
