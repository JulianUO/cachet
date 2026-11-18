@props([
    'date',
    'status',
    'events' => [],
    'durations' => [],
    'aggregate' => false,
    'older' => false,
])

@php
    use App\Support\UptimeBar;
    use Cachet\Enums\ComponentStatusEnum;
    use Illuminate\Support\Carbon;

    $noData = $status === null || $status === ComponentStatusEnum::unknown;
    $label = $noData ? __('cachet::system_status.no_data') : $status->getLabel();
    $downtime = UptimeBar::downtime($durations);
    $related = collect($events)->filter(fn (array $event): bool => ! empty($event['url']))->values();
@endphp

<span data-component="system-status-day"
      x-data="{ tooltipOpen: false }"
      @mouseenter="tooltipOpen = true"
      @mouseleave="tooltipOpen = false"
      @focusin="tooltipOpen = true"
      @focusout="tooltipOpen = false"
      x-ref="anchor"
      tabindex="0"
      aria-label="{{ Carbon::parse($date)->format('M j, Y') }}: {{ $label }}"
      @class(['uptime-day', 'uptime-day--older' => $older])>
    <span data-slot="bar" class="uptime-bar uptime-bar--{{ UptimeBar::modifier($status) }}"></span>
    <template x-teleport="body">
        <span x-show="tooltipOpen"
              x-cloak
              x-anchor.top.offset.8="$refs.anchor"
              role="tooltip"
              class="pointer-events-auto z-30 w-72 max-w-[calc(100vw-2rem)] rounded-lg bg-white p-3 text-left shadow-lg ring-1 ring-zinc-900/10 dark:bg-zinc-800 dark:ring-white/15">
            <time datetime="{{ $date }}" class="block text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ Carbon::parse($date)->translatedFormat('j M Y') }}</time>

            @if ($noData)
                <span class="mt-2 block text-sm text-zinc-500 dark:text-zinc-400" data-slot="summary">{{ __('status_history.no_data_day') }}</span>
            @elseif ($downtime === [])
                <span class="mt-2 block text-sm text-zinc-500 dark:text-zinc-400" data-slot="summary">{{ __('status_history.no_downtime') }}</span>
            @else
                <span class="uptime-tip-list mt-2 block" data-slot="downtime">
                    @foreach ($downtime as $row)
                        <span class="flex items-center justify-between gap-3 text-sm" data-slot="downtime-row">
                            <span class="flex min-w-0 items-center gap-2 font-medium text-zinc-900 dark:text-zinc-100">
                                <span class="uptime-dot uptime-bar--{{ UptimeBar::modifier($row['status']) }}" aria-hidden="true"></span>
                                <span class="truncate">{{ $row['status']->getLabel() }}</span>
                            </span>
                            <span class="uptime-tip-duration shrink-0 text-zinc-500 dark:text-zinc-400" data-slot="duration">{{ UptimeBar::duration($row['seconds']) }}</span>
                        </span>
                    @endforeach
                </span>
            @endif

            @if ($related->isNotEmpty())
                <span class="uptime-tip-related mt-3 block border-t border-zinc-900/10 dark:border-white/15">
                    <span class="uptime-tip-heading block text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ __('status_history.related') }}</span>
                    <span class="uptime-tip-list uptime-tip-gap block text-sm">
                        @foreach ($related as $event)
                            <a href="{{ $event['url'] }}" class="block break-words font-medium text-accent-content underline-offset-2 hover:underline" data-slot="event-link">{{ $event['label'] }}</a>
                        @endforeach
                    </span>
                </span>
            @endif

            <a href="{{ route('cachet.status-page.history', ['month' => Carbon::parse($date)->format('Y-m'), 'day' => $date]) }}"
               class="mt-3 inline-flex text-xs font-medium text-accent-content underline-offset-2 hover:underline"
               data-slot="history-day-link">
                {{ __('status_history.view_on_calendar') }}
            </a>
        </span>
    </template>
</span>
