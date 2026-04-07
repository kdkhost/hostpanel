<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Fila dedicada para email */
    public string $queue = 'email';

    /** Máximo de 3 tentativas */
    public int $tries = 3;

    /** Timeout por tentativa (segundos) */
    public int $timeout = 30;

    /** Backoff exponencial entre tentativas (segundos) */
    public array $backoff = [60, 300, 600];

    public function __construct(
        public readonly string  $toEmail,
        public readonly string  $toName,
        public readonly string  $subject,
        public readonly string  $htmlBody,
        public readonly ?int    $notifiableId   = null,
        public readonly ?string $notifiableType = null,
        public readonly ?string $templateSlug   = null,
        public readonly array   $headers        = [],
    ) {}

    public function middleware(): array
    {
        return [
            new RateLimited('email'),
        ];
    }

    public function handle(): void
    {
        $log = $this->createOrFindLog();

        try {
            Mail::send([], [], function ($message) {
                $message->to($this->toEmail, $this->toName)
                    ->subject($this->subject)
                    ->html($this->htmlBody)
                    ->replyTo(config('mail.from.address'), config('mail.from.name'));

                // Headers para evitar lista negra
                $message->getHeaders()
                    ->addTextHeader('X-Mailer', config('app.name') . ' Mailer')
                    ->addTextHeader('X-Priority', '3')
                    ->addTextHeader('Precedence', 'bulk')
                    ->addTextHeader(
                        'List-Unsubscribe',
                        '<mailto:' . config('mail.from.address') . '?subject=unsubscribe>'
                    );

                foreach ($this->headers as $name => $value) {
                    $message->getHeaders()->addTextHeader($name, $value);
                }
            });

            $log?->update(['status' => 'sent', 'sent_at' => now(), 'attempts' => $this->attempts()]);

        } catch (\Throwable $e) {
            $log?->update([
                'status'   => $this->attempts() >= $this->tries ? 'failed' : 'pending',
                'error'    => $e->getMessage(),
                'attempts' => $this->attempts(),
            ]);

            Log::error("SendEmailJob failed [{$this->toEmail}]: " . $e->getMessage());

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        NotificationLog::where('channel', 'email')
            ->where('recipient', $this->toEmail)
            ->where('status', 'pending')
            ->latest()
            ->first()
            ?->update(['status' => 'failed', 'error' => $e->getMessage()]);
    }

    private function createOrFindLog(): ?NotificationLog
    {
        if (!$this->notifiableId) return null;

        return NotificationLog::create([
            'notifiable_type' => $this->notifiableType ?? 'client',
            'notifiable_id'   => $this->notifiableId,
            'channel'         => 'email',
            'message'         => $this->subject,
            'recipient'       => $this->toEmail,
            'status'          => 'pending',
            'template_slug'   => $this->templateSlug,
        ]);
    }
}
