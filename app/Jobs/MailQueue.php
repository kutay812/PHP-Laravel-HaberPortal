<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\ContactMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $maxExceptions = 1;
    public int $timeout = 30;

    public function __construct(
        public string $email,
        public ?string $name = null,
        public ?string $subject = null,
        public string $body = '',
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(): void
    {
        try {
            Log::info("MailQueue: Mail gönderiliyor → {$this->email}");
            Mail::to($this->email)->send(new ContactMail(
                name: $this->name ?? '',
                subjectLine: $this->subject ?? 'Haber Portalı Bildirimi',
                messageBody: $this->body,
            ));
            Log::info("MailQueue: Mail gönderildi → {$this->email}");
        } catch (\Throwable $e) {
            Log::error("MailQueue: Mail gönderilemedi → {$this->email}, Hata: " . $e->getMessage());
            throw $e;
        }
    }
}
