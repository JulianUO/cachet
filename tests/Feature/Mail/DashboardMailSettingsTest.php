<?php

use App\Filament\Pages\Integrations\ManageMail;
use App\Mail\AppliesMailTransportSettings;
use App\Models\User;
use App\Settings\MailTransportSettings;
use Cachet\Settings\MailSettings;
use Livewire\Livewire;

it('uses env mail until a dashboard mailer is configured', function (): void {
    $settings = app(MailSettings::class);
    $settings->mailer = null;
    $settings->save();

    expect($settings->configured())->toBeFalse()
        ->and(config('mail.default'))->not->toBe('cachet');
});

it('applies dashboard mail settings including encryption', function (): void {
    $settings = app(MailSettings::class);
    $settings->mailer = 'smtp';
    $settings->host = 'smtp.example.com';
    $settings->port = 587;
    $settings->username = 'mailer';
    $settings->password = 'secret';
    $settings->from_address = 'status@example.com';
    $settings->from_name = 'Cachet';
    $settings->save();

    $transport = app(MailTransportSettings::class);
    $transport->encryption = 'tls';
    $transport->save();

    AppliesMailTransportSettings::apply();

    expect(config('mail.default'))->toBe('cachet')
        ->and(config('mail.mailers.cachet.host'))->toBe('smtp.example.com')
        ->and(config('mail.mailers.cachet.encryption'))->toBe('tls')
        ->and(config('mail.from.address'))->toBe('status@example.com');
});

it('allows admins to save mail settings from the integrations page', function (): void {
    $admin = User::forceCreate([
        'name' => 'Admin',
        'email' => 'mail-admin@example.com',
        'password' => 'password',
        'is_admin' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(ManageMail::class)
        ->fillForm([
            'mailer' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 465,
            'encryption' => 'ssl',
            'username' => 'smtp-user',
            'password' => 'smtp-pass',
            'from_address' => 'noreply@example.com',
            'from_name' => 'Status',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(MailSettings::class)->refresh();
    $transport = app(MailTransportSettings::class)->refresh();

    expect($settings->mailer)->toBe('smtp')
        ->and($settings->host)->toBe('smtp.example.com')
        ->and($settings->port)->toBe(465)
        ->and($settings->username)->toBe('smtp-user')
        ->and($settings->password)->toBe('smtp-pass')
        ->and($settings->from_address)->toBe('noreply@example.com')
        ->and($transport->encryption)->toBe('ssl')
        ->and(config('mail.default'))->toBe('cachet')
        ->and(config('mail.mailers.cachet.encryption'))->toBe('ssl');
});

it('keeps existing mail secrets when username and password are left blank', function (): void {
    $settings = app(MailSettings::class);
    $settings->mailer = 'smtp';
    $settings->host = 'smtp.example.com';
    $settings->port = 587;
    $settings->username = 'keep-user';
    $settings->password = 'keep-password';
    $settings->from_address = 'status@example.com';
    $settings->from_name = 'Cachet';
    $settings->save();

    $transport = app(MailTransportSettings::class);
    $transport->encryption = 'tls';
    $transport->save();

    $admin = User::forceCreate([
        'name' => 'Admin',
        'email' => 'keep-mail@example.com',
        'password' => 'password',
        'is_admin' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(ManageMail::class)
        ->fillForm([
            'mailer' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => '',
            'password' => '',
            'from_address' => 'status@example.com',
            'from_name' => 'Cachet Status',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(MailSettings::class)->refresh();

    expect($settings->username)->toBe('keep-user')
        ->and($settings->password)->toBe('keep-password')
        ->and($settings->from_name)->toBe('Cachet Status');
});

it('shows the mail integrations page without revealing secrets', function (): void {
    $settings = app(MailSettings::class);
    $settings->mailer = 'smtp';
    $settings->host = 'smtp.example.com';
    $settings->username = 'hidden-user';
    $settings->password = 'hidden-password';
    $settings->from_address = 'status@example.com';
    $settings->save();

    $admin = User::forceCreate([
        'name' => 'Admin',
        'email' => 'view-mail@example.com',
        'password' => 'password',
        'is_admin' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(ManageMail::class)
        ->assertSuccessful()
        ->assertSee('Outbound mail')
        ->assertSee('SMTP host')
        ->assertSee('Encryption')
        ->assertSee('From address')
        ->assertDontSee('hidden-password')
        ->assertSee('Dashboard (wins over env)');
});
