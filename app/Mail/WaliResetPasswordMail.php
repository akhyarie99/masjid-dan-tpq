<?php

namespace App\Mail;

use App\Models\WaliAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WaliResetPasswordMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public WaliAccount $account,
        public string $resetUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Password Portal Wali',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.wali-reset-password',
            with: [
                'resetUrl' => $this->resetUrl,
            ],
        );
    }
}
