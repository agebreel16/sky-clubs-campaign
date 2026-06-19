<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\Login;

class AdminLogin extends Login
{
    protected string $view = 'filament.pages.admin-login';

    protected static string $layout = 'filament.layouts.auth-login';
}
