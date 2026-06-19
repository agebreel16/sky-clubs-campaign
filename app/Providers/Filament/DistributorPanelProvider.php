<?php

namespace App\Providers\Filament;

use App\Filament\DistributorPanel\Pages\DistributorLogin;
use App\Filament\DistributorPanel\Pages\MyProfile;
use App\Filament\DistributorPanel\Widgets\ClubBreakdownWidget;
use App\Filament\DistributorPanel\Widgets\DistributorOverviewWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DistributorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('distributor')
            ->path('distributor')
            ->authGuard('distributor')
            ->login(DistributorLogin::class)
            ->font('Alexandria')
            ->brandName('SKY CLUB')
            ->colors([
                'primary' => Color::Teal,
                'danger'  => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'info'    => Color::Blue,
            ])
            ->favicon(asset('favicon.ico'))
            ->discoverResources(app_path('Filament/DistributorPanel/Resources'), 'App\\Filament\\DistributorPanel\\Resources')
            ->discoverPages(app_path('Filament/DistributorPanel/Pages'), 'App\\Filament\\DistributorPanel\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(app_path('Filament/DistributorPanel/Widgets'), 'App\\Filament\\DistributorPanel\\Widgets')
            ->widgets([
                DistributorOverviewWidget::class,
                ClubBreakdownWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationGroups([
                'وكلائي',
                'حسابي',
            ]);
    }
}
