@php
    $status = $this->getStatus();
@endphp

<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit">
                {{ __('Save Slack settings') }}
            </x-filament::button>
        </div>
    </form>

    <div class="mt-8 space-y-6">
        <x-filament::section>
            <x-slot name="heading">{{ __('Delivery status') }}</x-slot>
            <x-slot name="description">
                {{ __('Readiness after the saved settings. Failures are visible here and never include webhook URLs or bot tokens.') }}
            </x-slot>

            <dl class="grid gap-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Ready to send') }}</dt>
                    <dd class="font-medium">{{ $status['ready'] ? __('Yes') : __('No — enable and configure a delivery method') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Active delivery methods') }}</dt>
                    <dd class="font-medium">
                        @if ($status['delivery_methods'])
                            {{ collect($status['delivery_methods'])->map(fn (string $method): string => match ($method) {
                                'incoming_webhook' => __('Incoming webhook'),
                                'bot_token' => __('Bot token + channel'),
                                default => $method,
                            })->implode(', ') }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Incoming webhook') }}</dt>
                    <dd class="font-medium">{{ $status['webhook_configured'] ? __('Configured') : __('Not set') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Bot token + channel') }}</dt>
                    <dd class="font-medium">
                        {{ $status['bot_configured'] ? __('Configured') : __('Not set') }}
                        @if ($status['channel'])
                            <span class="font-mono text-xs text-gray-500">({{ $status['channel'] }})</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Last success') }}</dt>
                    <dd class="font-medium">{{ $status['last_success_at'] ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Last failure') }}</dt>
                    <dd class="font-medium text-right">
                        @if ($status['last_failure'])
                            <div>{{ $status['last_failure']['at'] ?? '—' }}</div>
                            <div class="text-xs text-danger-600 dark:text-danger-400">{{ $status['last_failure']['event'] ?? '' }}: {{ $status['last_failure']['message'] ?? '' }}</div>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('RSS fallback (legacy)') }}</dt>
                    <dd class="font-mono text-xs break-all">{{ $status['rss_fallback'] }}</dd>
                </div>
            </dl>
        </x-filament::section>
    </div>
</x-filament-panels::page>
