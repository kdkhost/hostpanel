<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Jobs\SendWhatsAppJob;
use App\Models\Client;
use App\Models\EmailTemplate;
use App\Models\InAppNotification;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function send(Client|int $client, string $trigger, array $variables = []): void
    {
        if (is_int($client)) {
            $client = Client::findOrFail($client);
        }

        $this->sendEmail($client, $trigger, $variables);
        $this->sendWhatsApp($client, $trigger, $variables);
        $this->sendInApp($client, $trigger, $variables);
    }

    public function sendEmail(Client $client, string $trigger, array $variables = []): void
    {
        if (!$client->email) return;

        $template = EmailTemplate::where('slug', $trigger)->where('active', true)->first();
        if (!$template) return;

        $subject = $this->interpolate($template->subject, $variables);
        $body    = $this->interpolate($template->body_html, $variables);

        SendEmailJob::dispatch(
            toEmail:        $client->email,
            toName:         $client->name,
            subject:        $subject,
            htmlBody:       $body,
            notifiableId:   $client->id,
            notifiableType: Client::class,
            templateSlug:   $trigger,
        )->onQueue('email');
    }

    public function sendWhatsApp(Client $client, string $trigger, array $variables = []): void
    {
        $phone = $client->whatsapp ?? $client->mobile ?? null;
        if (!$phone) return;

        if (!config('hostpanel.modules.whatsapp') || !config('hostpanel.evolution_api.url')) return;

        $template = \App\Models\WhatsappTemplate::where('slug', $trigger)->where('active', true)->first();
        if (!$template) return;

        $message = $this->interpolate($template->message, $variables);

        SendWhatsAppJob::dispatch(
            phone:          $phone,
            message:        $message,
            notifiableId:   $client->id,
            notifiableType: Client::class,
            templateSlug:   $trigger,
        )->onQueue('whatsapp');
    }

    public function sendInApp(Client $client, string $trigger, array $variables = []): void
    {
        $titles = [
            'welcome'           => 'Bem-vindo ao HostPanel!',
            'order_created'     => 'Pedido criado com sucesso',
            'payment_approved'  => 'Pagamento confirmado',
            'invoice_overdue'   => 'Fatura em atraso',
            'service_suspended' => 'Serviço suspenso',
            'service_active'    => 'Serviço ativado',
            'ticket_reply'      => 'Nova resposta no seu ticket',
            'ticket_created'    => 'Ticket criado',
        ];

        $icons  = ['payment_approved' => 'check-circle', 'invoice_overdue' => 'alert-triangle', 'service_suspended' => 'pause-circle'];
        $colors = ['payment_approved' => 'green', 'invoice_overdue' => 'red', 'service_suspended' => 'orange'];

        InAppNotification::create([
            'client_id'  => $client->id,
            'title'      => $this->interpolate($titles[$trigger] ?? ucfirst(str_replace('_', ' ', $trigger)), $variables),
            'message'    => $variables['message'] ?? '',
            'icon'       => $icons[$trigger]  ?? 'bell',
            'color'      => $colors[$trigger] ?? 'blue',
            'action_url' => $variables['action_url'] ?? null,
        ]);
    }

    protected function interpolate(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', (string) $value, $text);
        }
        return $text;
    }
}
