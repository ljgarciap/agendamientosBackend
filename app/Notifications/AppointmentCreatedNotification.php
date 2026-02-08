<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentCreatedNotification extends Notification
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
        return (new MailMessage)
            ->subject('Nueva Cita Agendada: ' . $this->appointment->service->name)
            ->greeting('Hola, Administrador')
            ->line('Se ha agendado una nueva cita en el sistema.')
            ->line('Servicio: ' . $this->appointment->service->name)
            ->line('Cliente: ' . $this->appointment->user->name)
            ->line('Fecha/Hora: ' . $this->appointment->scheduled_at)
            ->line('Ubicación: ' . $this->appointment->location)
            ->action('Ver Cita', url('/admin/appointments/' . $this->appointment->id))
            ->line('Gracias por su gestión.');
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
            'title' => 'Nueva Cita Agendada',
            'message' => 'El cliente ' . $this->appointment->user->name . ' ha solicitado ' . $this->appointment->service->name,
            'type' => 'new_appointment',
        ];
    }
}
