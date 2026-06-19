<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyDocumentRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'company_id',
        'requested_by',
        'document_name',
        'reason',
        'recipient_email',
        'status',
        'sent_at',
        'failure_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
