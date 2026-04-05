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
        $profile = $this->log->customerProfile;

        if ($this->step === 'compare') {
            return new Content(
                view: 'emails.abandoned-funnel-promotional',
                with: [
                    'logId'        => $this->log->id,
                    'customerName' => $profile?->full_name,
                ],
            );
        }

        $selectedInsurance = $profile?->selected_insurance;

        return new Content(
            view: 'emails.abandoned-funnel',
            with: [
                'logId'            => $this->log->id,
                'customerName'     => $profile?->full_name,
                'vehicleMake'      => $profile?->vehicle_make,
                'vehicleModel'     => $profile?->vehicle_model,
                'vehicleYear'      => $profile?->manufacturing_year,
                'totalPrice'       => $profile?->total_price,
                'insuranceCompany' => $selectedInsurance['name'] ?? null,
                'insuranceType'    => $profile?->insurance_type,
            ],
        );
    }

    private function getSubject(): string
    {
        $profile = $this->log->customerProfile;
        $name = $profile?->full_name;

        if ($this->step === 'compare') {
            return $name
                ? "{$name}، خصم 30% بانتظارك على تأميني"
                : 'خصم 30% على باقات التأمين - اختر باقتك الآن';
        }

        $price = $profile?->total_price;

        if ($name && $price) {
            return "{$name}، وثيقتك بـ " . number_format((float) $price, 2) . ' ر.س محجوزة على اسمك';
        }

        if ($name) {
            return "{$name}، أكمل الدفع - وثيقتك محجوزة";
        }

        return 'وثيقتك محجوزة - أكمل الدفع قبل انتهاء العرض';
    }
}
