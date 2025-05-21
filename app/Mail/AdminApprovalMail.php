<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminApprovalMail extends Mailable
{
    use Queueable, SerializesModels;
    public $user_info;
    public $subject;
    public $user_type;
    public $approve_status;

    /**
     * Create a new message instance.
     */
    public function __construct($user_info, $subject, $user_type, $approve_status)
    {
        $this->user_info = $user_info;
        $this->subject = $subject;
        $this->user_type = $user_type;
        $this->approve_status = $approve_status;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.adminApprove',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
