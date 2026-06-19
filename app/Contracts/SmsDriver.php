<?php

namespace App\Contracts;

interface SmsDriver
{
    public function send(string $to, string $message): void;
}
