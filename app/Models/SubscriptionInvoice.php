<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'invoice_number',
        'plan_name',
        'amount',
        'period_start',
        'period_end',
        'invoice_date',
        'due_date',
        'status',
        'payment_provider',
        'payment_method',
        'external_order_id',
        'external_transaction_id',
        'payment_payload',
        'paid_at',
        'auto_generated',
        'billed_for_renewal_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'payment_payload' => 'array',
        'auto_generated' => 'boolean',
        'billed_for_renewal_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
