<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentStatusChangedNotification extends Notification
{
    use Queueable;

    protected $appointment;

    /**
     * Create a new notification instance.
     */
    public function __construct($appointment)
    {
        $this->appointment = $appointment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = strtoupper($this->appointment->status);
        switch($this->appointment->status) {
            case 'confirmed': $statusLabel = 'CONFIRMADA'; break;
            case 'completed': $statusLabel = 'COMPLETADA'; break;
            case 'cancelled': $statusLabel = 'CANCELADA'; break;
            case 'in_progress': $statusLabel = 'EN PROGRESO'; break;
        }

        return (new MailMessage)
            ->subject('Actualización de tu Cita: ' . $statusLabel)
            ->greeting('Hola, ' . $notifiable->name)
            ->line('Tu cita para ' . $this->appointment->service->name . ' ha cambiado de estado.')
            ->line('Nuevo estado: ' . $statusLabel)
            ->line('Fecha/Hora: ' . $this->appointment->scheduled_at)
            ->action('Ver mis Citas', url('/client/appointments'))
            ->line('Gracias por confiar en nosotros.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'title' => 'Actualización de Cita',
            'message' => 'Tu cita para ' . $this->appointment->service->name . ' ahora está ' . $this->appointment->status,
            'type' => 'status_change',
            'status' => $this->appointment->status,
        ];
    }
}
