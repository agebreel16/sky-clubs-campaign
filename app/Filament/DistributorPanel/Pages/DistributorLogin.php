<?php

namespace App\Filament\DistributorPanel\Pages;

use Filament\Auth\Pages\Login;

class DistributorLogin extends Login
{
    protected string $view = 'filament.pages.distributor-login';

    protected static string $layout = 'filament.layouts.auth-login';
}
