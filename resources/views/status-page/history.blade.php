<x-cachet::cachet page="history">
    <x-cachet::header />

    <main data-slot="main" data-component="history-calendar" class="status-page container mx-auto flex max-w-5xl flex-col gap-8 px-4 py-10 sm:px-6 lg:px-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    <a href="{{ route('cachet.status-page') }}" class="underline-offset-2 hover:underline">{{ __('cachet::system_status.title') }}</a>
                    <span aria-hidden="true"> / </span>
                    <span>{{ __('status_history.title') }}</span>
                </p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ $month->translatedFormat('F Y') }}
                </h1>
            </div>

            <nav class="inline-flex items-center gap-0.5 rounded-lg bg-zinc-100 p-0.5 ring-1 ring-zinc-900/10 dark:bg-zinc-800/80 dark:ring-white/15" aria-label="{{ __('status_history.title') }}">
                <a href="{{ route('cachet.status-page.history', ['month' => $previousMonth]) }}"
                   class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium text-zinc-600 transition hover:bg-white hover:text-zinc-900 hover:shadow-sm dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-white">
                    <x-heroicon-m-chevron-left class="size-3.5" />
                    {{ __('status_history.previous_month') }}
                </a>
                @if ($canGoForward)
                    <a href="{{ route('cachet.status-page.history', ['month' => $nextMonth]) }}"
                       class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium text-zinc-600 transition hover:bg-white hover:text-zinc-900 hover:shadow-sm dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-white">
                        {{ __('status_history.next_month') }}
                        <x-heroicon-m-chevron-right class="size-3.5" />
                    </a>
                @endif
            </nav>
        </header>

        <div class="overflow-hidden rounded-xl bg-white ring-1 ring-zinc-900/10 dark:bg-zinc-900/40 dark:ring-white/15">
            <div class="grid grid-cols-7 border-b border-zinc-900/10 bg-zinc-50 text-center text-xs font-medium uppercase tracking-wide text-zinc-500 dark:border-white/15 dark:bg-white/[0.03] dark:text-zinc-400">
                @foreach ($weekdays as $weekday)
                    <div class="px-2 py-3">{{ $weekday }}</div>
                @endforeach
            </div>

            <div class="grid grid-cols-7 auto-rows-fr">
                @foreach ($cells as $cell)
                    @php
                        $isSelected = $selectedDay && $cell['date']->isSameDay($selectedDay);
                        $classes = 'min-h-20 border-b border-r border-zinc-900/10 p-2 text-left transition dark:border-white/10 ';
                        $classes .= $cell['in_month'] ? 'bg-white dark:bg-transparent ' : 'bg-zinc-50/80 text-zinc-400 dark:bg-white/[0.02] dark:text-zinc-600 ';
                        $classes .= $isSelected ? 'ring-2 ring-inset ring-accent ' : 'hover:bg-zinc-50 dark:hover:bg-white/[0.04] ';
                    @endphp
                    <a href="{{ route('cachet.status-page.history', ['month' => $month->format('Y-m'), 'day' => $cell['date']->toDateString()]) }}"
                       data-slot="calendar-day"
                       data-date="{{ $cell['date']->toDateString() }}"
                       data-has-events="{{ $cell['has_events'] ? '1' : '0' }}"
                       class="{{ $classes }}">
                        <span class="flex items-center justify-between gap-1">
                            <span class="text-sm font-medium {{ $cell['date']->isToday() ? 'text-accent-content' : 'text-zinc-900 dark:text-zinc-100' }}">
                                {{ $cell['date']->day }}
                            </span>
                            @if ($cell['has_events'])
                                <span class="size-2 rounded-full bg-amber-400" aria-hidden="true"></span>
                            @endif
                        </span>
                        @if ($cell['has_events'] && $cell['in_month'])
                            <span class="mt-2 block space-y-1">
                                @foreach ($cell['incidents']->take(2) as $incident)
                                    <span class="block truncate text-[11px] font-medium text-amber-800 dark:text-amber-300">{{ $incident->name }}</span>
                                @endforeach
                                @foreach ($cell['schedules']->take(2) as $schedule)
                                    <span class="block truncate text-[11px] font-medium text-orange-800 dark:text-orange-300">{{ $schedule->name }}</span>
                                @endforeach
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        <div class="flex flex-wrap gap-4 text-xs text-zinc-500 dark:text-zinc-400">
            <span class="inline-flex items-center gap-2"><span class="size-2 rounded-full bg-emerald-500"></span>{{ __('status_history.legend_operational') }}</span>
            <span class="inline-flex items-center gap-2"><span class="size-2 rounded-full bg-amber-400"></span>{{ __('status_history.legend_disrupted') }}</span>
            <span class="inline-flex items-center gap-2"><span class="size-2 rounded-full bg-zinc-300 dark:bg-zinc-600"></span>{{ __('status_history.legend_empty') }}</span>
        </div>

        @if ($selected)
            <section data-slot="day-detail" class="rounded-xl bg-white p-5 ring-1 ring-zinc-900/10 dark:bg-zinc-900/40 dark:ring-white/15 sm:p-6">
                <h2 class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ __('status_history.day_events', ['date' => $selected['date']->toFormattedDateString()]) }}
                </h2>

                @if ($selected['incidents']->isEmpty() && $selected['schedules']->isEmpty())
                    <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('status_history.no_events') }}</p>
                @else
                    @if ($selected['incidents']->isNotEmpty())
                        <h3 class="mt-4 text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ __('status_history.incidents') }}</h3>
                        <ul class="mt-2 space-y-2">
                            @foreach ($selected['incidents'] as $incident)
                                <li>
                                    <a href="{{ route('cachet.status-page.incident', $incident) }}" class="text-sm font-medium text-accent-content underline-offset-2 hover:underline">
                                        {{ $incident->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($selected['schedules']->isNotEmpty())
                        <h3 class="mt-4 text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ __('status_history.maintenance') }}</h3>
                        <ul class="mt-2 space-y-2">
                            @foreach ($selected['schedules'] as $schedule)
                                <li>
                                    <a href="{{ route('cachet.status-page.schedule', $schedule) }}" class="text-sm font-medium text-accent-content underline-offset-2 hover:underline">
                                        {{ $schedule->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            </section>
        @endif

        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            <a href="{{ route('cachet.status-page') }}" class="underline-offset-2 hover:underline">{{ __('status_history.view_timeline') }}</a>
        </p>
    </main>

    <x-cachet::footer />
</x-cachet::cachet>
