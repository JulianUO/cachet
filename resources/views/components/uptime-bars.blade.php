@props([
    'row',
    'days',
    'aggregate' => false,
])

@php
    // On small screens only the most recent days are shown, like Atlassian Statuspage.
    $mobileDays = min($days, 30);
    $total = $row['days']->count();
@endphp

<div {{ $attributes->merge(['class' => 'uptime-bars']) }} data-slot="indicators" style="--uptime-days: {{ $total }}">
    @foreach ($row['days'] as $date => $status)
        <x-cachet::system-status-day
            :$date
            :$status
            :events="$row['events']->get($date, [])"
            :durations="$row['durations']->get($date, [])"
            :$aggregate
            :older="$loop->index < $total - $mobileDays" />
    @endforeach
</div>
<div data-slot="axis" {{ $attributes->only('x-show') }} class="uptime-axis flex justify-between text-[11px] tracking-tight text-zinc-400 dark:text-zinc-500">
    <span>
        <span @class(['uptime-axis--full' => $mobileDays < $days])>{{ __('status_history.days_ago', ['days' => $days]) }}</span>
        @if ($mobileDays < $days)
            <span class="uptime-axis--mobile">{{ __('status_history.days_ago', ['days' => $mobileDays]) }}</span>
        @endif
    </span>
    <span>{{ __('status_history.today') }}</span>
</div>
