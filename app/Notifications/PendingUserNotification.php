<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingUserNotification extends Notification
{
    use Queueable;

    public $data;

    /**
     * Create a new notification instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
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
        $data = $this->data;
        return (new MailMessage)
                    ->greeting('Hello '.$data['firstname'].'!')
                    ->line('Thank you for creating an account on '. env('APP_NAME').'.')
                    ->line('To complete the verification process, please click on the link below.')
                    ->action('Verify Email', url('/api/v1/create-password/token='.$data['token'].'&email='. $data['email']))
                    ->line('If the above link does not work, please copy and paste the following URL into your browser');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
