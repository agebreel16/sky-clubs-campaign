<?php

namespace App\Channels;

use App\Contracts\SmsDriver;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        $to = $notifiable->routeNotificationForSms();
        if (!$to) {
            return;
        }

        $message = $notification->toSms($notifiable);
        app(SmsDriver::class)->send($to, $message);
    }
}
