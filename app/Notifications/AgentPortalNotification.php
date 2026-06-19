<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class AgentPortalNotification extends Notification
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly bool   $sendSms = false,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = [WebPushChannel::class];

        if ($this->sendSms && $notifiable->phone) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->action('فتح البوابة', route('agent.portal.dashboard', ['uuid' => $notifiable->agent_id]));
    }

    public function toSms(object $notifiable): string
    {
        return "{$this->title}: {$this->body}";
    }
}
