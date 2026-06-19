<?php

namespace App\Sms;

use App\Contracts\SmsDriver;
use Illuminate\Support\Facades\Log;

class NullSmsDriver implements SmsDriver
{
    public function send(string $to, string $message): void
    {
        Log::channel('daily')->info('SMS (null driver)', ['to' => $to, 'message' => $message]);
    }
}
