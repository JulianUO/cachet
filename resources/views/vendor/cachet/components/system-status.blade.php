{{ \Cachet\Facades\CachetView::renderHook(\Cachet\View\RenderHook::STATUS_PAGE_SYSTEM_STATUS_BEFORE) }}
<section data-component="system-status" class="status-overview">
    <header class="status-overview__masthead flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 data-slot="title" class="font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">{{ __('cachet::system_status.title') }}</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                <a href="{{ route('cachet.status-page.history') }}" class="text-accent-content underline-offset-2 hover:underline" data-slot="uptime-link">
                    {{ __('status_history.uptime_over_past_days', ['days' => 90]) }}
                </a>
            </p>
        </div>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            <time datetime="{{ $from->toDateString() }}">{{ $from->format('M j') }}</time>
            <span aria-hidden="true">–</span>
            <time datetime="{{ $to->toDateString() }}">{{ $to->format('M j, Y') }}</time>
        </p>
    </header>

    <div class="divide-y divide-zinc-900/10 dark:divide-white/15">
        @foreach ($groups as $group)
            {{ \Cachet\Facades\CachetView::renderHook(\Cachet\View\RenderHook::STATUS_PAGE_SYSTEM_STATUS_GROUP_BEFORE) }}
            <div data-component="system-status-group" data-component-group-id="{{ $group['model']->getKey() }}" x-data x-disclosure>
                <button data-slot="trigger" x-disclosure:button class="w-full px-5 py-4 text-left transition hover:bg-zinc-50/60 dark:hover:bg-white/[0.02] sm:px-6">
                    <div class="flex items-center justify-between gap-4">
                        <span class="flex min-w-0 items-center gap-2">
                            <span x-show="! $disclosure.isOpen" class="flex shrink-0 text-accent-content" aria-hidden="true">
                                <x-heroicon-m-plus class="size-4" />
                            </span>
                            <span x-show="$disclosure.isOpen" x-cloak class="flex shrink-0 text-accent-content" aria-hidden="true">
                                <x-heroicon-m-minus class="size-4" />
                            </span>
                            <span class="truncate font-semibold text-zinc-900 dark:text-zinc-100">{{ $group['model']->name }}</span>
                            <span class="hidden shrink-0 text-sm text-zinc-500 dark:text-zinc-400 sm:inline">{{ trans_choice('cachet::system_status.component_count', $group['components']->count(), ['count' => $group['components']->count()]) }}</span>
                        </span>
                        @php($groupStatus = $group['components']->pluck('status')->sortByDesc(fn ($status) => $status->severity())->first() ?? \Cachet\Enums\ComponentStatusEnum::operational)
                        <span data-slot="status-badge" class="{{ \App\Support\StatusBadge::classes($groupStatus) }}">{{ $groupStatus->getLabel() }}</span>
                    </div>
                    <div data-slot="indicators" x-show="! $disclosure.isOpen" class="mt-3 grid h-8 gap-px sm:h-4" style="grid-template-columns: repeat({{ $group['days']->count() }}, minmax(2px, 1fr))">
                        @foreach ($group['days'] as $date => $status)
                            <x-cachet::system-status-day :$date :$status :events="$group['events']->get($date, [])" :aggregate="true" />
                        @endforeach
                    </div>
                    <div data-slot="axis" x-show="! $disclosure.isOpen" class="mt-1.5 flex justify-between text-[11px] tracking-tight text-zinc-400 dark:text-zinc-500">
                        <span>{{ __('status_history.days_ago', ['days' => 90]) }}</span>
                        <span>{{ __('status_history.today') }}</span>
                    </div>
                </button>

                <div data-slot="content" x-disclosure:panel x-collapse class="border-t border-zinc-900/10 bg-zinc-50/40 dark:border-white/15 dark:bg-white/[0.02]">
                    <div class="divide-y divide-zinc-900/10 dark:divide-white/15">
                        @foreach ($group['components'] as $componentRow)
                            {{ \Cachet\Facades\CachetView::renderHook(\Cachet\View\RenderHook::STATUS_PAGE_SYSTEM_STATUS_COMPONENT_BEFORE) }}
                            <div data-component="system-status-component" data-component-id="{{ $componentRow['model']->getKey() }}" class="px-5 py-4 sm:px-6">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <x-cachet::system-status-component-name :model="$componentRow['model']" />
                                    </div>
                                    <div class="flex shrink-0 items-center gap-3">
                                        <span class="hidden text-sm text-zinc-500 dark:text-zinc-400 lg:inline">{{ $componentRow['uptime'] === null ? __('cachet::system_status.no_data') : __('cachet::system_status.uptime', ['uptime' => number_format($componentRow['uptime'], 2)]) }}</span>
                                        <span data-slot="status-badge" class="{{ \App\Support\StatusBadge::classes($componentRow['status']) }}">{{ $componentRow['status']->getLabel() }}</span>
                                    </div>
                                </div>
                                <div data-slot="indicators" class="mt-3 grid h-8 gap-px sm:h-4" style="grid-template-columns: repeat({{ $componentRow['days']->count() }}, minmax(2px, 1fr))">
                                    @foreach ($componentRow['days'] as $date => $status)
                                        <x-cachet::system-status-day :$date :$status :events="$componentRow['events']->get($date, [])" />
                                    @endforeach
                                </div>
                                <div data-slot="axis" class="mt-1.5 flex justify-between text-[11px] tracking-tight text-zinc-400 dark:text-zinc-500">
                                    <span>{{ __('status_history.days_ago', ['days' => 90]) }}</span>
                                    <span>{{ __('status_history.today') }}</span>
                                </div>
                            </div>
                            {{ \Cachet\Facades\CachetView::renderHook(\Cachet\View\RenderHook::STATUS_PAGE_SYSTEM_STATUS_COMPONENT_AFTER) }}
                        @endforeach
                    </div>
                </div>
            </div>
            {{ \Cachet\Facades\CachetView::renderHook(\Cachet\View\RenderHook::STATUS_PAGE_SYSTEM_STATUS_GROUP_AFTER) }}
        @endforeach

        @foreach ($ungroupedComponents as $componentRow)
            {{ \Cachet\Facades\CachetView::renderHook(\Cachet\View\RenderHook::STATUS_PAGE_SYSTEM_STATUS_COMPONENT_BEFORE) }}
            <div data-component="system-status-component" data-component-id="{{ $componentRow['model']->getKey() }}" class="px-5 py-4 sm:px-6">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-2">
                        <x-cachet::system-status-component-name :model="$componentRow['model']" emphasized />
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <span class="hidden text-sm text-zinc-500 dark:text-zinc-400 lg:inline">{{ $componentRow['uptime'] === null ? __('cachet::system_status.no_data') : __('cachet::system_status.uptime', ['uptime' => number_format($componentRow['uptime'], 2)]) }}</span>
                        <span data-slot="status-badge" class="{{ \App\Support\StatusBadge::classes($componentRow['status']) }}">{{ $componentRow['status']->getLabel() }}</span>
                    </div>
                </div>
                <div data-slot="indicators" class="mt-3 grid h-8 gap-px sm:h-4" style="grid-template-columns: repeat({{ $componentRow['days']->count() }}, minmax(2px, 1fr))">
                    @foreach ($componentRow['days'] as $date => $status)
                        <x-cachet::system-status-day :$date :$status :events="$componentRow['events']->get($date, [])" />
                    @endforeach
                </div>
                <div data-slot="axis" class="mt-1.5 flex justify-between text-[11px] tracking-tight text-zinc-400 dark:text-zinc-500">
                    <span>{{ __('status_history.days_ago', ['days' => 90]) }}</span>
                    <span>{{ __('status_history.today') }}</span>
                </div>
            </div>
            {{ \Cachet\Facades\CachetView::renderHook(\Cachet\View\RenderHook::STATUS_PAGE_SYSTEM_STATUS_COMPONENT_AFTER) }}
        @endforeach
    </div>
</section>
{{ \Cachet\Facades\CachetView::renderHook(\Cachet\View\RenderHook::STATUS_PAGE_SYSTEM_STATUS_AFTER) }}
