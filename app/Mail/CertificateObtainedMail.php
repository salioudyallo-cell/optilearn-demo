<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CertificateObtainedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Course $course,
        public Certificate $certificate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre certificat pour « '.$this->course->title.' » est disponible',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.certificate-obtained',
            with: [
                'userName' => $this->user->name,
                'courseTitle' => $this->course->title,
                'serial' => $this->certificate->serial,
                'verifyUrl' => route('certificate.verify', $this->certificate->serial),
                'certificatesUrl' => route('certificates.index'),
            ],
        );
    }
}
