<?php

namespace App\Sms;

use App\Contracts\SmsDriver;
use Illuminate\Support\Facades\Http;

class UnifonicSmsDriver implements SmsDriver
{
    public function send(string $to, string $message): void
    {
        Http::withHeaders(['Authorization' => 'Bearer ' . config('services.unifonic.api_key')])
            ->post('https://api.unifonic.com/rest/SMS/messages', [
                'SenderID'    => config('services.unifonic.sender_id'),
                'Recipient'   => $to,
                'Body'        => $message,
                'responseType' => 'json',
            ]);
    }
}
