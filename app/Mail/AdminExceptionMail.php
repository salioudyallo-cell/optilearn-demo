<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminExceptionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $exceptionClass,
        public string $exceptionMessage,
        public string $location,
        public string $context,
        public string $trace,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '['.config('brand.short_name').'] Erreur : '.class_basename($this->exceptionClass),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.admin-exception',
            with: [
                'exceptionClass' => $this->exceptionClass,
                'exceptionMessage' => $this->exceptionMessage,
                'location' => $this->location,
                'context' => $this->context,
                'occurredAt' => now()->toDateTimeString(),
                'trace' => $this->trace,
            ],
        );
    }
}
