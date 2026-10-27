@php
    $status = $this->getStatus();
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">{{ __('Native Slack notifications') }}</x-slot>
            <x-slot name="description">
                {{ __('Cachet posts incidents, component status changes, and maintenance to Slack. Preferred over the /slack.rss feed. Configure via SLACK_* environment variables.') }}
            </x-slot>

            <dl class="grid gap-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Feature flag') }}</dt>
                    <dd class="font-medium">{{ $status['enabled'] ? __('Enabled') : __('Disabled') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Ready to send') }}</dt>
                    <dd class="font-medium">{{ $status['ready'] ? __('Yes') : __('No — enable and configure a delivery method') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Delivery methods') }}</dt>
                    <dd class="font-medium">{{ $status['delivery_methods'] ? implode(', ', $status['delivery_methods']) : '—' }}</dd>
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
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Bot display name') }}</dt>
                    <dd class="font-medium">{{ $status['username'] ?: '—' }}</dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('Events') }}</x-slot>
            <x-slot name="description">
                {{ __('Which Cachet events post to Slack when the feature is ready.') }}
            </x-slot>

            <dl class="grid gap-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Incidents (create / update)') }}</dt>
                    <dd class="font-medium">{{ $status['events']['incidents'] ? __('On') : __('Off') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Component status changes') }}</dt>
                    <dd class="font-medium">{{ $status['events']['component_status'] ? __('On') : __('Off') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Maintenance / schedules') }}</dt>
                    <dd class="font-medium">{{ $status['events']['schedules'] ? __('On') : __('Off') }}</dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('Delivery health') }}</x-slot>
            <x-slot name="description">
                {{ __('Failures are logged and shown here so silent drops are visible. The RSS fallback remains available for legacy Slack RSS apps.') }}
            </x-slot>

            <dl class="grid gap-3 text-sm">
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

        <x-filament::section>
            <x-slot name="heading">{{ __('Configuration') }}</x-slot>
            <div class="prose prose-sm dark:prose-invert max-w-none">
                <ol>
                    <li>{{ __('Set SLACK_NOTIFICATIONS_ENABLED=true') }}</li>
                    <li>{{ __('Preferred: set SLACK_WEBHOOK_URL to an Incoming Webhook URL, or') }}</li>
                    <li>{{ __('Alternative: set SLACK_BOT_TOKEN (xoxb-…) and SLACK_CHANNEL (C… or #name)') }}</li>
                    <li>{{ __('Optional toggles: SLACK_NOTIFY_INCIDENTS, SLACK_NOTIFY_COMPONENT_STATUS, SLACK_NOTIFY_SCHEDULES') }}</li>
                    <li>{{ __('Incidents and schedules still respect their “notify subscribers” flags') }}</li>
                </ol>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
