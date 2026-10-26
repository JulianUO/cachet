@props([
    'date',
    'status',
    'events' => [],
    'aggregate' => false,
])

@php
    use Cachet\Enums\ComponentStatusEnum;
    use Illuminate\Support\Carbon;

    $label = $status === null || $status === ComponentStatusEnum::unknown
        ? __('cachet::system_status.no_data')
        : $status->getLabel();

    $eventCollection = collect($events);
    $incidentLike = $eventCollection->filter(fn (array $event): bool => ($event['icon'] ?? null) === 'cachet-incident'
        || in_array($event['status'] ?? null, [
            ComponentStatusEnum::partial_outage,
            ComponentStatusEnum::major_outage,
            ComponentStatusEnum::performance_issues,
        ], true));
    $maintenanceLike = $eventCollection->filter(fn (array $event): bool => ($event['icon'] ?? null) === 'cachet-maintenance'
        || ($event['status'] ?? null) === ComponentStatusEnum::under_maintenance);

    $summary = match (true) {
        $status === null, $status === ComponentStatusEnum::unknown => __('cachet::system_status.no_data_description'),
        $incidentLike->isNotEmpty() && $aggregate => trans_choice('status_history.components_had_outage', $incidentLike->count(), ['count' => $incidentLike->count()]),
        $incidentLike->isNotEmpty() && $status === ComponentStatusEnum::major_outage => __('status_history.had_major_outage'),
        $incidentLike->isNotEmpty() => __('status_history.had_partial_outage'),
        $maintenanceLike->isNotEmpty() => __('status_history.had_maintenance'),
        $status === ComponentStatusEnum::operational && $eventCollection->isEmpty() => __('cachet::system_status.no_activity'),
        $status === ComponentStatusEnum::operational => __('cachet::system_status.no_activity'),
        default => __('cachet::system_status.status_continued', ['status' => $label]),
    };

    $barColor = match ($status) {
        ComponentStatusEnum::operational => 'bg-emerald-500',
        ComponentStatusEnum::major_outage => 'bg-red-500',
        null, ComponentStatusEnum::unknown => 'bg-zinc-100 dark:bg-zinc-800',
        default => 'bg-amber-400',
    };
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
      class="relative block rounded-sm {{ $barColor }} hover:brightness-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-accent">
    <template x-teleport="body">
        <span x-show="tooltipOpen"
              x-cloak
              x-anchor.top.offset.8="$refs.anchor"
              role="tooltip"
              class="pointer-events-auto z-30 w-72 max-w-[calc(100vw-2rem)] rounded-lg bg-white p-3 text-left shadow-lg ring-1 ring-zinc-900/10 dark:bg-zinc-800 dark:ring-white/15">
            <time datetime="{{ $date }}" class="block text-sm font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ Carbon::parse($date)->format('j M Y') }}</time>
            <span class="mt-2 flex items-start gap-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                @svg(($status ?? ComponentStatusEnum::unknown)->getIcon(), 'mt-0.5 size-4 shrink-0 '.match ($status) {
                    ComponentStatusEnum::operational => 'text-emerald-500',
                    ComponentStatusEnum::major_outage => 'text-red-500',
                    null, ComponentStatusEnum::unknown => 'text-zinc-400',
                    default => 'text-amber-400',
                }, ['aria-hidden' => 'true'])
                <span class="min-w-0 break-words">{{ $summary }}</span>
            </span>

            @if ($eventCollection->isNotEmpty())
                <span class="mt-3 block space-y-2 border-t border-zinc-900/10 pt-3 text-sm text-zinc-700 dark:border-white/15 dark:text-zinc-200">
                    @foreach ($eventCollection as $event)
                        <span class="flex items-start gap-2">
                            @svg($event['icon'], 'mt-0.5 size-4 shrink-0 '.match ($event['status']) {
                                ComponentStatusEnum::operational => 'text-emerald-500',
                                ComponentStatusEnum::major_outage => 'text-red-500',
                                default => 'text-amber-400',
                            }, ['data-event-icon' => $event['icon'], 'aria-hidden' => 'true'])
                            @if (! empty($event['url']))
                                <a href="{{ $event['url'] }}" class="min-w-0 break-words font-medium text-accent-content underline-offset-2 hover:underline" data-slot="event-link">{{ $event['label'] }}</a>
                            @else
                                <span class="min-w-0 break-words">{{ $event['label'] }}</span>
                            @endif
                        </span>
                    @endforeach
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
