<?php

namespace App\Notifications;

use App\Models\InstanciaWhatsApp;
use App\Models\Tenant;
use App\Models\User;
use App\Mail\WhatsAppOfflineMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WhatsAppDesconectadoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public InstanciaWhatsApp $instancia,
        public string $linkReconnect,
    ) {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $notifiable */
        return (new MailMessage)
            ->subject("⚠️ WhatsApp da loja {$this->tenant->nome_loja} desconectado")
            ->greeting("Olá {$notifiable->name}!")
            ->line("O número WhatsApp da loja **{$this->tenant->nome_loja}** foi desconectado.")
            ->line("Número: " . ($this->instancia->numero_whatsapp ?? 'N/A'))
            ->line("O bot WhatsApp está parado. Os clientes não conseguem fazer encomendas.")
            ->action("Reconectar WhatsApp", $this->linkReconnect)
            ->line("Abre o painel e escaneia o novo QR code para reconectar.")
            ->salutation("Equipe WhatsApp Marketplace");
    }
}
