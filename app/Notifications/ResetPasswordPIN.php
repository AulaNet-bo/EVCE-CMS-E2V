<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordPIN extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $pin;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $pin)
    {
        $this->pin = $pin;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Recuperación de Contraseña - Electropoint')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en Electropoint.')
            ->line('Por favor, ingresa el siguiente código de verificación de 6 dígitos en tu aplicación móvil para continuar:')
            ->line('**' . $this->pin . '**')
            ->line('Este código de seguridad es de un solo uso y expirará en 15 minutos.')
            ->line('Si tú no solicitaste este cambio, puedes ignorar este correo de forma segura. Tu contraseña seguirá siendo la misma.')
            ->salutation('Atentamente, el equipo Electropoint.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'pin' => $this->pin,
        ];
    }
}
