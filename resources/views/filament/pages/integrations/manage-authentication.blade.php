@php
    $status = $this->getStatus();
@endphp

<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit">
                {{ __('Save authentication settings') }}
            </x-filament::button>
        </div>
    </form>

    <div class="mt-8 space-y-6">
        <x-filament::section>
            <x-slot name="heading">{{ __('Effective status') }}</x-slot>
            <x-slot name="description">
                {{ __('Readiness after saved settings. Secrets are never shown here.') }}
            </x-slot>

            <dl class="grid gap-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Config source') }}</dt>
                    <dd class="font-medium">
                        {{ $status['source'] === 'dashboard' ? __('Dashboard (wins over env)') : __('Environment bootstrap') }}
                    </dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('Local authentication') }}</x-slot>
            <dl class="grid gap-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt>
                    <dd class="font-medium">{{ $status['local']['enabled'] ? __('Enabled') : __('Disabled') }}</dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('OpenID Connect (OIDC)') }}</x-slot>
            <dl class="grid gap-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt>
                    <dd class="font-medium">{{ $status['oidc']['enabled'] ? __('Enabled') : __('Disabled') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Credentials') }}</dt>
                    <dd class="font-medium">{{ $status['oidc']['configured'] ? __('Configured') : __('Missing') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Client secret') }}</dt>
                    <dd class="font-medium">{{ $status['oidc']['secret_configured'] ? __('Saved') : __('Not set') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Issuer') }}</dt>
                    <dd class="font-mono text-xs break-all">{{ $status['oidc']['issuer'] ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Client ID') }}</dt>
                    <dd class="font-mono text-xs break-all">{{ $status['oidc']['client_id'] ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Callback URL') }}</dt>
                    <dd class="font-mono text-xs break-all">{{ $status['oidc']['redirect'] ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Scopes') }}</dt>
                    <dd class="font-mono text-xs">{{ $status['oidc']['scopes'] ?: '—' }}</dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('LDAP') }}</x-slot>
            <dl class="grid gap-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt>
                    <dd class="font-medium">{{ $status['ldap']['enabled'] ? __('Enabled') : __('Disabled') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Connection') }}</dt>
                    <dd class="font-medium">{{ $status['ldap']['configured'] ? __('Configured') : __('Missing') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Bind password') }}</dt>
                    <dd class="font-medium">{{ $status['ldap']['secret_configured'] ? __('Saved') : __('Not set') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Host') }}</dt>
                    <dd class="font-mono text-xs">{{ $status['ldap']['host'] ?: '—' }}:{{ $status['ldap']['port'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('TLS / SSL') }}</dt>
                    <dd class="font-medium">
                        {{ $status['ldap']['use_tls'] ? __('TLS') : ($status['ldap']['use_ssl'] ? __('SSL') : __('Off')) }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Base DN') }}</dt>
                    <dd class="font-mono text-xs break-all">{{ $status['ldap']['base_dn'] ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Filter') }}</dt>
                    <dd class="font-mono text-xs break-all">{{ $status['ldap']['filter'] ?: '—' }}</dd>
                </div>
            </dl>
        </x-filament::section>
    </div>
</x-filament-panels::page>
