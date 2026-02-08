<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentAssignedNotification extends Notification
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
            ->subject('Nueva Cita Asignada: ' . $this->appointment->service->name)
            ->greeting('Hola, ' . $notifiable->name)
            ->line('Se te ha asignado una nueva cita.')
            ->line('Servicio: ' . $this->appointment->service->name)
            ->line('Cliente: ' . $this->appointment->user->name)
            ->line('Fecha/Hora: ' . $this->appointment->scheduled_at)
            ->line('Ubicación: ' . $this->appointment->location)
            ->action('Ver mi Agenda', url('/employee/home'))
            ->line('¡Buen trabajo!');
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
            'title' => 'Nueva Tarea Asignada',
            'message' => 'Se te ha asignado la cita de ' . $this->appointment->service->name . ' para el cliente ' . $this->appointment->user->name,
            'type' => 'assignment',
        ];
    }
}
