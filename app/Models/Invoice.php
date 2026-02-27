<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory, BelongsToCompany;

    public function getRouteKeyName(): string
    {
        return 'invoice_number';
    }

    protected static function booted(): void
    {
        static::creating(function (self $invoice): void {
            if (!empty($invoice->public_id)) {
                return;
            }

            do {
                $publicId = (string) Str::ulid();
            } while (self::query()->where('public_id', $publicId)->exists());

            $invoice->public_id = $publicId;
        });
    }

    protected $fillable = [
        'company_id',
        'public_id',
        'client_id',
        'bank_account_id',
        'is_down_payment',
        'parent_invoice_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'tax_total',
        'total',
        'notes',
    ];

    protected $casts = [
        'is_down_payment' => 'boolean',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function parentInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_invoice_id');
    }

    public function continuationInvoices(): HasMany
    {
        return $this->hasMany(self::class, 'parent_invoice_id');
    }
}
