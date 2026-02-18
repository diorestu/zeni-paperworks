<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id',
        'bank_name',
        'account_name',
        'account_number',
        'is_default',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
