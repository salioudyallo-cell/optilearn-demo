<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccessGrantedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Course $course,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre accès à la formation « '.$this->course->title.' » est activé',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.access-granted',
            with: [
                'userName' => $this->user->name,
                'courseTitle' => $this->course->title,
                'courseUrl' => route('learn.course', $this->course),
            ],
        );
    }
}
