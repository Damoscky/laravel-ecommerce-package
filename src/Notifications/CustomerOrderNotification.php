<?php

namespace SbscPackage\Ecommerce\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerOrderNotification extends Notification
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
                    ->greeting('Hello '.$data['name'].'!')
                    ->subject("Order Placed - ". env('APP_NAME'))
                    ->line("Confirmation Order Number: ".$data['orders']['orderID'])
                    ->line("We’re happy to let you know that we’ve received your order.")
                    ->line("Once your package has been shipped, we will send you an email with a tracking number and link so you can see the movement of your package.")
                    ->line("If you have any questions, contact us via email ".env('MAIL_FROM_ADDRESS'))
                    ->line('We are here to help!');
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