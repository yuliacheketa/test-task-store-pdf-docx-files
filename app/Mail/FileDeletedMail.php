<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FileDeletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $filename,
        public readonly string $reason,
        public readonly string $deletedAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "File deleted: {$this->filename}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.file-deleted',
        );
    }
}

