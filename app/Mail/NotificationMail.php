<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
    ) {}

    public function build(): self
    {
        return $this
            ->subject($this->title)
            ->view('emails.notification')
            ->with([
                'title' => $this->title,
                'body'  => $this->body,
                'url'   => $this->url,
            ]);
    }
}
