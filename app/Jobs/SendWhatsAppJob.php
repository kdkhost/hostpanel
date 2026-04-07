<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Fila dedicada para WhatsApp */
    public string $queue = 'whatsapp';

    /** Máximo de tentativas */
    public int $tries = 3;

    /** Timeout por tentativa */
    public int $timeout = 20;

    /** Backoff entre tentativas (segundos) */
    public array $backoff = [120, 600, 1800];

    /**
     * @param string      $phone           Número destino (apenas dígitos, com ou sem 55)
     * @param string      $message         Texto da mensagem
     * @param string      $type            'text' | 'media'
     * @param string|null $mediaUrl        URL da mídia (se type=media)
     * @param string|null $mediaCaption    Legenda da mídia
     * @param int|null    $notifiableId    ID do model notificável
     * @param string|null $notifiableType  Tipo do model ('App\Models\Client', etc.)
     * @param string|null $templateSlug    Slug do template usado
     * @param int         $minDelaySeconds Delay mínimo antes de enviar (anti-ban)
     * @param int         $maxDelaySeconds Delay máximo antes de enviar (anti-ban)
     */
    public function __construct(
        public readonly string  $phone,
        public readonly string  $message,
        public readonly string  $type            = 'text',
        public readonly ?string $mediaUrl        = null,
        public readonly ?string $mediaCaption    = null,
        public readonly ?int    $notifiableId    = null,
        public readonly ?string $notifiableType  = null,
        public readonly ?string $templateSlug    = null,
        public readonly int     $minDelaySeconds = 5,
        public readonly int     $maxDelaySeconds = 15,
    ) {}

    public function middleware(): array
    {
        return [
            new RateLimited('whatsapp'),
        ];
    }

    public function handle(): void
    {
        // Delay humanizado aleatório para simular comportamento humano e evitar ban
        $delay = rand($this->minDelaySeconds, $this->maxDelaySeconds);
        sleep($delay);

        $baseUrl  = rtrim(config('hostpanel.evolution_api.url', ''), '/');
        $apiKey   = config('hostpanel.evolution_api.key', '');
        $instance = config('hostpanel.evolution_api.instance', '');

        if (!$baseUrl || !$apiKey || !$instance) {
            Log::warning("SendWhatsAppJob: Evolution API não configurada.");
            return;
        }

        $phone = $this->normalizePhone($this->phone);
        $log   = $this->createLog();

        try {
            if ($this->type === 'media' && $this->mediaUrl) {
                $response = Http::withHeaders([
                    'apikey'       => $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(15)->post("{$baseUrl}/message/sendMedia/{$instance}", [
                    'number'    => $phone,
                    'mediatype' => 'image',
                    'media'     => $this->mediaUrl,
                    'caption'   => $this->mediaCaption ?? '',
                ]);
            } else {
                $response = Http::withHeaders([
                    'apikey'       => $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(15)->post("{$baseUrl}/message/sendText/{$instance}", [
                    'number' => $phone,
                    'text'   => $this->message,
                    'delay'  => $delay * 1000,
                ]);
            }

            if ($response->successful()) {
                $log?->update(['status' => 'sent', 'sent_at' => now(), 'attempts' => $this->attempts()]);
            } else {
                $error = $response->body();
                Log::warning("SendWhatsAppJob HTTP {$response->status()} [{$phone}]: {$error}");
                $log?->update(['status' => 'failed', 'error' => $error, 'attempts' => $this->attempts()]);

                if ($response->status() >= 500) {
                    throw new \RuntimeException("Evolution API server error: {$error}");
                }
            }

        } catch (\Throwable $e) {
            $log?->update([
                'status'   => $this->attempts() >= $this->tries ? 'failed' : 'pending',
                'error'    => $e->getMessage(),
                'attempts' => $this->attempts(),
            ]);
            Log::error("SendWhatsAppJob exception [{$phone}]: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SendWhatsAppJob permanently failed [{$this->phone}]: " . $e->getMessage());
    }

    private function normalizePhone(string $number): string
    {
        $clean = preg_replace('/\D/', '', $number);
        if (!str_starts_with($clean, '55')) {
            $clean = '55' . $clean;
        }
        return $clean;
    }

    private function createLog(): ?NotificationLog
    {
        if (!$this->notifiableId) return null;

        return NotificationLog::create([
            'notifiable_type' => $this->notifiableType ?? 'client',
            'notifiable_id'   => $this->notifiableId,
            'channel'         => 'whatsapp',
            'message'         => $this->message,
            'recipient'       => $this->phone,
            'status'          => 'pending',
            'template_slug'   => $this->templateSlug,
        ]);
    }
}
