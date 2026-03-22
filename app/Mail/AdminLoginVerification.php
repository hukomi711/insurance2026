<?php

namespace App\Mail;

use App\Models\AdminLoginCode;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminLoginVerification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AdminLoginCode $loginCode,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'رمز تأكيد الدخول - تأمينكم',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin-login-verification',
            with: [
                'code' => $this->loginCode->code,
                'expiresAt' => $this->loginCode->expires_at,
                'ip' => $this->loginCode->ip_address,
            ],
        );
    }
}
