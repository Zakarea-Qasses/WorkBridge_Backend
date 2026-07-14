<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WalletRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'status',
        'payment_note',
        'deposit_reference',
        'deposit_receipt_path',
        'withdrawal_details',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $appends = [
        'deposit_receipt_url',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function getDepositReceiptUrlAttribute(): ?string
    {
        if (! $this->deposit_receipt_path) {
            return null;
        }

        return Storage::disk('public')->url($this->deposit_receipt_path);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
