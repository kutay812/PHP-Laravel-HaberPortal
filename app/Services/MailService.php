<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\MailQueue;
use Illuminate\Support\Facades\Log;

class MailService
{
    /**
     * @return array<string, mixed>
     */
    public function sendMail(string $email, ?string $name = null, ?string $subject = null, ?string $message = null): array
    {
        try {
            MailQueue::dispatch($email, $name, $subject, $message ?? '')
                ->delay(now()->addSeconds(5));

            Log::info('Mail kuyruğa eklendi', [
                'email'     => $email,
                'name'      => $name ?? 'N/A',
                'timestamp' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Mail kuyruğa başarıyla eklendi.',
                'data'    => ['queued_at' => now()],
            ];
        } catch (\Throwable $e) {
            Log::error('Mail kuyruğa eklenirken hata oluştu', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Mail gönderilemedi.',
                'error'   => $e->getMessage(),
                'code'    => 500,
            ];
        }
    }

    /**
     * @param list<string> $emails
     * @return array<string, mixed>
     */
    public function sendBulkMail(array $emails, ?string $subject = null, string $body = ''): array
    {
        $successCount = 0;
        $failedEmails = [];

        foreach ($emails as $index => $email) {
            try {
                MailQueue::dispatch($email, null, $subject, $body)
                    ->delay(now()->addSeconds(5 + ($index * 2)));
                $successCount++;
            } catch (\Throwable $e) {
                $failedEmails[] = $email;
                Log::error('Toplu mail - tek email hatası', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Toplu mail kuyruğa eklendi', [
            'total_emails'  => count($emails),
            'success_count' => $successCount,
            'failed_count'  => count($failedEmails),
            'timestamp'     => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Toplu mail işlemi tamamlandı.',
            'data'    => [
                'total'         => count($emails),
                'success'       => $successCount,
                'failed'        => count($failedEmails),
                'failed_emails' => $failedEmails,
                'queued_at'     => now(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueueStatus(): array
    {
        return [
            'success' => true,
            'message' => 'Kuyruk durumu getirildi.',
            'data'    => [
                'queue_active' => true,
                'timestamp'    => now(),
                'message'      => 'Mail kuyruk sistemi aktif',
            ],
        ];
    }
}
