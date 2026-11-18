{{ \Cachet\Facades\CachetView::renderHook(\Cachet\View\RenderHook::STATUS_PAGE_SYSTEM_STATUS_BEFORE) }}
{{-- Atlassian Statuspage-style uptime bars. Plain CSS because the status page ships a prebuilt Tailwind bundle. --}}
<style>
    .uptime-bars { display: flex; align-items: stretch; height: 34px; }
    .uptime-day { flex: 1 1 0; min-width: 0; display: flex; justify-content: center; cursor: pointer; outline: none; }
    .uptime-bar { display: block; width: 60%; max-width: 10px; min-width: 2px; height: 100%; border-radius: 1px; background-color: var(--uptime-color); transition: opacity .1s ease-in-out; }
    .uptime-day:hover .uptime-bar, .uptime-day:focus-visible .uptime-bar { opacity: .6; }
    .uptime-day:focus-visible .uptime-bar { outline: 2px solid var(--uptime-color); outline-offset: 2px; }
    .uptime-dot { display: inline-block; width: .625rem; height: .625rem; flex-shrink: 0; border-radius: 9999px; background-color: var(--uptime-color); }
    .uptime-bar--operational { --uptime-color: #2fcc66; }
    .uptime-bar--degraded { --uptime-color: #f1c40f; }
    .uptime-bar--partial { --uptime-color: #e67e22; }
    .uptime-bar--major { --uptime-color: #e74c3c; }
    .uptime-bar--maintenance { --uptime-color: #3498db; }
    .uptime-bar--nodata { --uptime-color: #b3bac5; }
    .uptime-axis { margin-top: .375rem; }
    .uptime-axis--mobile { display: none; }
    .uptime-tip-list > * + * { margin-top: .375rem; }
    .uptime-tip-gap { margin-top: .375rem; }
    .uptime-tip-related { padding-top: .75rem; }
    .uptime-tip-heading { text-transform: uppercase; letter-spacing: .025em; }
    .uptime-tip-duration { font-variant-numeric: tabular-nums; }
    @media (prefers-color-scheme: dark) {
        .uptime-bar--nodata { --uptime-color: #52525b; }
    }
    @media (max-width: 639px) {
        .uptime-day--older { display: none; }
        .uptime-axis--full { display: none; }
        .uptime-axis--mobile { display: inline; }
    }
</style>
<section data-component="system-status" class="status-overview">
    <header class="status-overview__masthead flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 data-slot="title" class="font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">{{ __('cachet::system_status.title') }}</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                <a href="{{ route('cachet.status-page.history') }}" class="text-accent-content underline-offset-2 hover:underline" data-slot="uptime-link">
                    {{ __('status_history.uptime_over_past_days', ['days' => $days]) }}
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
                    <x-uptime-bars :row="$group" :$days :aggregate="true" x-show="! $disclosure.isOpen" class="mt-3" />
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
                                <x-uptime-bars :row="$componentRow" :$days class="mt-3" />
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
                <x-uptime-bars :row="$componentRow" :$days class="mt-3" />
            </div>
            {{ \Cachet\Facades\CachetView::renderHook(\Cachet\View\RenderHook::STATUS_PAGE_SYSTEM_STATUS_COMPONENT_AFTER) }}
        @endforeach
    </div>
</section>
{{ \Cachet\Facades\CachetView::renderHook(\Cachet\View\RenderHook::STATUS_PAGE_SYSTEM_STATUS_AFTER) }}
