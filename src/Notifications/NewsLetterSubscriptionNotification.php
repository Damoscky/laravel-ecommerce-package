<?php

namespace SbscPackage\Ecommerce\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewsLetterSubscriptionNotification extends Notification
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
        ->greeting('Hello '.$data['email'].'!')
        ->subject("Newsletter Subscription - ". env('APP_NAME'))
        ->line("Thank you for subscribing to our newsletter!")
        ->line('Stay tuned for the latest updates, exciting news, and exclusive content.')
        ->line('We promise to keep you informed about upcoming events, product launches, and industry insights.')
        ->line("Feel free to reach out to us if you have any suggestions or topics you'd like us to cover in our newsletters.")
        ->line('We appreciate your support and look forward to sharing valuable content with you!');
        
        
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