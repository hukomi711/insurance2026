<?php

namespace App\Mail;

use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedFunnelEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $step,
        public EmailLog $log,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->getSubject(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.abandoned-funnel',
            with: [
                'step'       => $this->step,
                'logId'      => $this->log->id,
                'customerName' => $this->log->customerProfile?->full_name,
            ],
        );
    }

    private function getSubject(): string
    {
        return match ($this->step) {
            'compare'         => 'عروض التأمين بانتظارك - وثيقة',
            'checkout'        => 'عرضك مازال متاح 🔥 أكمل طلبك الآن',
            'payment_waiting' => 'الدفع لم يكتمل ⚠️ أكمل العملية',
            'otp'             => 'خطوة واحدة تفصلك عن التأمين',
            default           => 'أكمل طلب التأمين الآن - وثيقة',
        };
    }
}
