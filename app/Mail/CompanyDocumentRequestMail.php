<?php

namespace App\Mail;

use App\Models\CompanyDocumentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyDocumentRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $companyName;

    public string $requestDate;

    public function __construct(public CompanyDocumentRequest $documentRequest)
    {
        $this->companyName = $documentRequest->company->company_name;
        $this->requestDate = $documentRequest->created_at->format('Y-m-d H:i');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'طلب مستند إضافي من إدارة WorkBridge',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.company-document-request',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
