<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_name',
        'monthly_price',
        'yearly_price',
        'invoice_limit',
    ];

    protected $casts = [
        'monthly_price' => 'integer',
        'yearly_price' => 'integer',
        'invoice_limit' => 'integer',
    ];
}

