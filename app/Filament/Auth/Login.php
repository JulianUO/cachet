<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Filament\Auth;

use App\Auth\AuthIntegrations;
use App\Auth\ExternalUserProvisioner;
use App\Auth\Ldap\LdapAuthenticator;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Timebox;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use SensitiveParameter;
use Throwable;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        if (! AuthIntegrations::passwordFormEnabled()) {
            $this->throwFailureValidationException();
        }

        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        /** @var SessionGuard $authGuard */
        $authGuard = Filament::auth();
        $credentials = $this->getCredentialsFromFormData($data);
        $remember = $data['remember'] ?? false;
        $timeboxDuration = (int) config('auth.timebox_duration', 200_000);

        $user = null;

        if (AuthIntegrations::localEnabled()) {
            $user = app(Timebox::class)->call(function (Timebox $timebox) use ($authGuard, $credentials, $remember) {
                $this->fireAttemptingEvent($authGuard, $credentials, $remember);

                $authProvider = $authGuard->getProvider(); /** @phpstan-ignore-line */
                $candidate = $authProvider->retrieveByCredentials($credentials);

                if ($candidate && filled($candidate->getAuthPassword()) && $authProvider->validateCredentials($candidate, $credentials)) {
                    if (! $this->isUserAllowedToAccessPanel($candidate)) {
                        $this->fireFailedEvent($authGuard, $candidate, $credentials);
                        $this->throwFailureValidationException();
                    }

                    $timebox->returnEarly();

                    return $candidate;
                }

                return null;
            }, $timeboxDuration);
        }

        if ($user === null && AuthIntegrations::ldapEnabled()) {
            $user = app(Timebox::class)->call(function (Timebox $timebox) use ($credentials) {
                try {
                    $identity = app(LdapAuthenticator::class)->attempt(
                        (string) ($credentials['email'] ?? ''),
                        (string) ($credentials['password'] ?? ''),
                    );
                } catch (Throwable $exception) {
                    report($exception);

                    return null;
                }

                if ($identity === null) {
                    return null;
                }

                try {
                    $provisioned = app(ExternalUserProvisioner::class)->provision(
                        'ldap',
                        $identity,
                        (bool) config('auth_integrations.ldap.auto_provision', true),
                        (bool) config('auth_integrations.ldap.default_is_admin', false),
                    );
                } catch (RuntimeException) {
                    return null;
                }

                if (! $this->isUserAllowedToAccessPanel($provisioned)) {
                    return null;
                }

                $timebox->returnEarly();

                return $provisioned;
            }, $timeboxDuration);
        }

        if ($user === null) {
            $this->userUndertakingMultiFactorAuthentication = null;
            $this->fireFailedEvent($authGuard, null, $credentials);
            $this->throwFailureValidationException();
        }

        $needsMultiFactorChallenge = app(Timebox::class)->call(function (Timebox $timebox) use ($user): bool {
            if (
                filled($this->userUndertakingMultiFactorAuthentication) &&
                (decrypt($this->userUndertakingMultiFactorAuthentication) === $user->getAuthIdentifier())
            ) {
                if ($this->isMultiFactorChallengeRateLimited($user)) {
                    return true;
                }

                $this->multiFactorChallengeForm->validate();

                return false;
            }

            $multiFactorChallenge = $this->getMultiFactorChallenge();

            if ($multiFactorAuthenticationProvider = $multiFactorChallenge->getFirstEnabledProvider($user)) {
                $this->userUndertakingMultiFactorAuthentication = encrypt($user->getAuthIdentifier());

                $multiFactorChallenge->beforeChallenge($user, $multiFactorAuthenticationProvider);
            }

            if (filled($this->userUndertakingMultiFactorAuthentication)) {
                $this->multiFactorChallengeForm->fill();

                return true;
            }

            if (Filament::getMultiFactorAuthenticationProviders() === []) {
                $timebox->returnEarly();
            }

            return false;
        }, $timeboxDuration);

        if ($needsMultiFactorChallenge) {
            return null;
        }

        $authGuard->login($user, $remember);
        session()->regenerate();

        return app(LoginResponse::class);
    }

    public function form(Schema $schema): Schema
    {
        if (! AuthIntegrations::passwordFormEnabled()) {
            return $schema->components([]);
        }

        return parent::form($schema);
    }

    protected function getEmailFormComponent(): Component
    {
        $label = AuthIntegrations::ldapEnabled() && ! AuthIntegrations::localEnabled()
            ? __('Username or email')
            : __('filament-panels::auth/pages/login.form.email.label');

        return TextInput::make('email')
            ->label($label)
            ->email(AuthIntegrations::localEnabled() && ! AuthIntegrations::ldapEnabled())
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        $actions = [];

        if (AuthIntegrations::passwordFormEnabled()) {
            $actions[] = $this->getAuthenticateFormAction();
        }

        if (AuthIntegrations::oidcEnabled()) {
            $actions[] = Action::make('oidc')
                ->label((string) config('auth_integrations.oidc.button_label', 'Sign in with SSO'))
                ->url(route('auth.oidc.redirect'))
                ->color(AuthIntegrations::passwordFormEnabled() ? 'gray' : 'primary');
        }

        return $actions;
    }

    public function getSubheading(): string | Htmlable | null
    {
        $errors = session('errors');

        if ($errors && $errors->has('oidc')) {
            return new HtmlString(
                '<span class="fi-text-danger text-sm">'.e($errors->first('oidc')).'</span>'
            );
        }

        if (! AuthIntegrations::passwordFormEnabled() && AuthIntegrations::oidcEnabled()) {
            return new HtmlString(__('Use your organization SSO to continue.'));
        }

        return parent::getSubheading();
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler(AuthIntegrations::passwordFormEnabled() ? 'authenticate' : null)
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
            ])
            ->visible(fn (): bool => blank($this->userUndertakingMultiFactorAuthentication));
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE),
                $this->getFormContentComponent(),
                $this->getMultiFactorChallengeFormContentComponent(),
                RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER),
            ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        $component = TextInput::make('password')
            ->label(__('filament-panels::auth/pages/login.form.password.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required();

        if (AuthIntegrations::localEnabled() && filament()->hasPasswordReset()) {
            $component->hint(new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="-1"> {{ __(\'filament-panels::auth/pages/login.actions.request_password_reset.label\') }}</x-filament::link>')));
        }

        return $component;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'email' => $data['email'],
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }
}
