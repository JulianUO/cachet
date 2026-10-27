@php
    $status = $this->getStatus();
@endphp

<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit">
                {{ __('Save mail settings') }}
            </x-filament::button>
        </div>
    </form>

    <div class="mt-8 space-y-6">
        <x-filament::section>
            <x-slot name="heading">{{ __('Effective mail status') }}</x-slot>
            <x-slot name="description">
                {{ __('Subscriber toggles remain under Settings → Notifications. Secrets are never shown here.') }}
            </x-slot>

            <dl class="grid gap-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Config source') }}</dt>
                    <dd class="font-medium">
                        {{ $status['source'] === 'dashboard' ? __('Dashboard (wins over env)') : __('Environment bootstrap') }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Mailer') }}</dt>
                    <dd class="font-mono text-xs">{{ $status['mailer'] ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Host / port') }}</dt>
                    <dd class="font-mono text-xs">{{ $status['host'] ?: '—' }}:{{ $status['port'] ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Encryption') }}</dt>
                    <dd class="font-medium">{{ $status['encryption'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('From') }}</dt>
                    <dd class="font-mono text-xs break-all">
                        {{ $status['from_name'] ? $status['from_name'].' <'.$status['from_address'].'>' : ($status['from_address'] ?: '—') }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Username') }}</dt>
                    <dd class="font-medium">{{ $status['username_configured'] ? __('Saved') : __('Not set') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Password') }}</dt>
                    <dd class="font-medium">{{ $status['password_configured'] ? __('Saved') : __('Not set') }}</dd>
                </div>
            </dl>
        </x-filament::section>
    </div>
</x-filament-panels::page>
