<?php

namespace App\Filament\DistributorPanel\Pages;

use App\Models\Distributor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class MyProfile extends Page
{
    use InteractsWithForms;

    protected static ?string $title = 'بياناتي الشخصية';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.distributor.pages.my-profile';

    public static function getNavigationIcon(): string { return 'heroicon-o-user-circle'; }

    public static function getNavigationGroup(): ?string { return 'حسابي'; }

    public static function getNavigationLabel(): string { return 'بياناتي الشخصية'; }

    public ?array $profileData = [];

    public function mount(): void
    {
        /** @var Distributor $distributor */
        $distributor = auth('distributor')->user();

        $this->profileData = [
            'name'   => $distributor->name,
            'phone'  => $distributor->phone,
            'email'  => $distributor->email,
            'region' => $distributor->region,
        ];
    }

    public function profileForm(Schema $schema): Schema
    {
        /** @var Distributor $distributor */
        $distributor = auth('distributor')->user();

        return $schema
            ->statePath('profileData')
            ->schema([
                Section::make('بياناتي الشخصية')
                    ->icon('heroicon-o-user-circle')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('الاسم الكامل')
                            ->required()
                            ->maxLength(200),

                        TextInput::make('phone')
                            ->label('رقم الجوال')
                            ->required()
                            ->tel()
                            ->maxLength(20)
                            ->unique(
                                table: 'distributors',
                                column: 'phone',
                                ignorable: $distributor
                            ),

                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->unique(
                                table: 'distributors',
                                column: 'email',
                                ignorable: $distributor
                            ),

                        TextInput::make('region')
                            ->label('المنطقة')
                            ->required()
                            ->maxLength(100),
                    ]),

                Section::make('تغيير كلمة المرور')
                    ->icon('heroicon-o-lock-closed')
                    ->description('اترك الحقول فارغة إذا لم تريد تغيير كلمة المرور.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('current_password')
                            ->label('كلمة المرور الحالية')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->nullable(),

                        TextInput::make('new_password')
                            ->label('كلمة المرور الجديدة')
                            ->password()
                            ->revealable()
                            ->nullable()
                            ->minLength(8)
                            ->dehydrated(false)
                            ->requiredWith('current_password'),

                        TextInput::make('new_password_confirmation')
                            ->label('تأكيد كلمة المرور الجديدة')
                            ->password()
                            ->revealable()
                            ->nullable()
                            ->same('new_password')
                            ->dehydrated(false)
                            ->requiredWith('new_password'),
                    ]),
            ]);
    }

    protected function getForms(): array
    {
        return ['profileForm'];
    }

    public function save(): void
    {
        $data = $this->profileForm->getState();

        /** @var Distributor $distributor */
        $distributor = auth('distributor')->user();

        if (!empty($data['current_password'])) {
            if (!Hash::check($data['current_password'], $distributor->password)) {
                Notification::make()
                    ->danger()
                    ->title('كلمة المرور الحالية غير صحيحة')
                    ->send();
                return;
            }
            $distributor->password = Hash::make($data['new_password']);
        }

        $distributor->name   = $data['name'];
        $distributor->phone  = $data['phone'];
        $distributor->email  = $data['email'];
        $distributor->region = $data['region'];
        $distributor->save();

        Notification::make()
            ->success()
            ->title('تم تحديث بياناتك بنجاح')
            ->send();
    }
}
