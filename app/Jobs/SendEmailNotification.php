<?php

namespace App\Jobs;

use App\Mail\NotificationMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $title,
        public string $message,
        public ?string $url = null,
    ) {}

    public function handle(): void
    {
        Mail::to($this->user->email)->send(new NotificationMail(
            title: $this->title,
            body: $this->message,
            url: $this->url,
        ));
    }
}
