<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Filament\Pages\Integrations;

use App\Auth\Permission;
use App\Mail\AppliesMailTransportSettings;
use App\Models\User;
use App\Settings\MailTransportSettings;
use Cachet\Filament\Pages\Settings\SettingsPage;
use Cachet\Mail\TestMail;
use Cachet\Settings\MailSettings;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Integrations → Mail — dashboard source of truth for outbound mail transport.
 * Uses core MailSettings; adds SMTP encryption + blank-to-keep secrets (Slack pattern).
 * Env MAIL_* remains bootstrap until a mailer is saved here (or under Settings → Notifications).
 */
class ManageMail extends SettingsPage
{
    protected static string $settings = MailSettings::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected string $view = 'filament.pages.integrations.manage-mail';

    public static function getNavigationGroup(): ?string
    {
        return __('cachet::navigation.integrations.label');
    }

    public static function getNavigationLabel(): string
    {
        return __('Mail');
    }

    public function getTitle(): string
    {
        return __('Mail');
    }

    public static function getNavigationSort(): ?int
    {
        return 15;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->isAdmin()
            || $user->hasPermission(Permission::SettingsManage)
        );
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Outbound mail'))
                    ->description(__('Preferred over MAIL_* environment variables once a mailer is saved. Leaving mailer empty keeps using env bootstrap. Redis/AWS remain Laravel infra env — not configured here.'))
                    ->columns(2)
                    ->schema([
                        Select::make('mailer')
                            ->label(__('Mailer'))
                            ->placeholder(__('Use environment (MAIL_MAILER)'))
                            ->options([
                                'smtp' => 'SMTP',
                                'sendmail' => 'Sendmail',
                                'log' => 'Log',
                            ])
                            ->nullable()
                            ->live()
                            ->columnSpanFull(),

                        TextInput::make('host')
                            ->label(__('SMTP host'))
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => $get('mailer') === 'smtp')
                            ->required(fn (Get $get): bool => $get('mailer') === 'smtp'),

                        TextInput::make('port')
                            ->label(__('SMTP port'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(65535)
                            ->placeholder('587')
                            ->visible(fn (Get $get): bool => $get('mailer') === 'smtp'),

                        Select::make('encryption')
                            ->label(__('Encryption'))
                            ->options([
                                '' => __('None'),
                                'tls' => 'TLS',
                                'ssl' => 'SSL',
                            ])
                            ->nullable()
                            ->visible(fn (Get $get): bool => $get('mailer') === 'smtp'),

                        TextInput::make('username')
                            ->label(__('Username'))
                            ->maxLength(255)
                            ->autocomplete(false)
                            ->visible(fn (Get $get): bool => $get('mailer') === 'smtp')
                            ->placeholder(fn (): string => $this->usernameConfigured()
                                ? __('Leave blank to keep the current username')
                                : '')
                            ->helperText(fn (): string => $this->usernameConfigured()
                                ? __('A username is already saved. Enter a new value to replace it.')
                                : __('Stored encrypted. Never re-shown or logged.')),

                        TextInput::make('password')
                            ->label(__('Password'))
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->autocomplete(false)
                            ->visible(fn (Get $get): bool => $get('mailer') === 'smtp')
                            ->placeholder(fn (): string => $this->passwordConfigured()
                                ? __('Leave blank to keep the current password')
                                : '')
                            ->helperText(fn (): string => $this->passwordConfigured()
                                ? __('A password is already saved. Enter a new value to replace it.')
                                : __('Stored encrypted. Never re-shown or logged.')),

                        TextInput::make('from_address')
                            ->label(__('From address'))
                            ->email()
                            ->maxLength(255)
                            ->required(fn (Get $get): bool => filled($get('mailer'))),

                        TextInput::make('from_name')
                            ->label(__('From name'))
                            ->maxLength(255)
                            ->nullable(),
                    ]),
            ]);
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestEmail')
                ->label(__('Send test email'))
                ->icon(Heroicon::OutlinedEnvelope)
                ->action(fn () => $this->sendTestEmail()),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $transport = app(MailTransportSettings::class);
        $data['encryption'] = $transport->encryption ?? '';
        $data['username'] = '';
        $data['password'] = '';

        // Preserve subscription flags from MailSettings without exposing them here.
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $settings = app(MailSettings::class);
        $transport = app(MailTransportSettings::class);

        $encryption = $data['encryption'] ?? null;
        unset($data['encryption']);

        $transport->encryption = filled($encryption) ? (string) $encryption : null;
        $transport->save();

        if (! filled($data['username'] ?? null)) {
            $data['username'] = $settings->username;
        }

        if (! filled($data['password'] ?? null)) {
            $data['password'] = $settings->password;
        }

        $data['username'] = filled($data['username'] ?? null) ? trim((string) $data['username']) : null;
        $data['password'] = filled($data['password'] ?? null) ? trim((string) $data['password']) : null;
        $data['host'] = filled($data['host'] ?? null) ? trim((string) $data['host']) : null;
        $data['from_address'] = filled($data['from_address'] ?? null) ? trim((string) $data['from_address']) : null;
        $data['from_name'] = filled($data['from_name'] ?? null) ? trim((string) $data['from_name']) : null;
        $data['port'] = filled($data['port'] ?? null) ? (int) $data['port'] : null;

        // Do not clobber subscription settings managed on Settings → Notifications.
        $data['allow_subscribers'] = $settings->allow_subscribers;
        $data['notify_long_running_incidents'] = $settings->notify_long_running_incidents;
        $data['long_running_incident_hours'] = $settings->long_running_incident_hours;

        return $data;
    }

    protected function afterSave(): void
    {
        AppliesMailTransportSettings::apply();
    }

    public function sendTestEmail(): void
    {
        $settings = app(MailSettings::class);
        $email = Filament::auth()->user()?->email;

        if (! filled($email)) {
            return;
        }

        try {
            AppliesMailTransportSettings::apply();

            $mailer = $settings->configured()
                ? Mail::build(AppliesMailTransportSettings::mailerConfig($settings))
                : Mail::mailer();

            $mailer->send(
                (new TestMail)
                    ->to($email)
                    ->from(
                        $settings->from_address ?? config('mail.from.address'),
                        $settings->from_name ?? config('mail.from.name'),
                    )
            );
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->title(__('Test email failed'))
                ->body($exception->getMessage())
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title(__('Test email sent to :email', ['email' => $email]))
            ->send();
    }

    private function usernameConfigured(): bool
    {
        return filled(app(MailSettings::class)->username);
    }

    private function passwordConfigured(): bool
    {
        return filled(app(MailSettings::class)->password);
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        $settings = app(MailSettings::class);
        $transport = app(MailTransportSettings::class);

        return [
            'source' => $settings->configured() ? 'dashboard' : 'env',
            'mailer' => $settings->configured()
                ? (string) $settings->mailer
                : (string) config('mail.default'),
            'host' => $settings->configured()
                ? (string) $settings->host
                : (string) config('mail.mailers.smtp.host'),
            'port' => $settings->configured()
                ? (string) ($settings->port ?? '')
                : (string) config('mail.mailers.smtp.port'),
            'encryption' => $settings->configured()
                ? (string) ($transport->encryption ?: '—')
                : (string) (config('mail.mailers.smtp.encryption') ?: '—'),
            'from_address' => (string) ($settings->from_address ?? config('mail.from.address')),
            'from_name' => (string) ($settings->from_name ?? config('mail.from.name')),
            'username_configured' => $this->usernameConfigured(),
            'password_configured' => $this->passwordConfigured(),
        ];
    }
}
