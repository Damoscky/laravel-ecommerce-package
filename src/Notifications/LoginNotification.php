<?php

namespace SbscPackage\Authentication\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginNotification extends Notification
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
                ->subject("Login Notification")
                ->line("Please be informed that your ". env('APP_NAME') ." account was accessed on ". \Carbon\Carbon::now())
                ->line('If you did not log on to your profile at the time detailed above, please email us at info@fanerp.com or click on the link below to reset your password.')
                ->action('Reset Password', url('/auth/reset-password'.'?email='. $data['email']))
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
