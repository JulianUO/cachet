<x-cachet::cachet page="status">
    <x-cachet::header />

    @php($appSettings = app(\Cachet\Settings\AppSettings::class))
    @php($siteName = $appSettings->name ?: config('cachet.title', 'Cachet'))

    <main data-slot="main" class="status-page container mx-auto flex max-w-5xl flex-col gap-8 px-4 py-10 sm:px-6 lg:px-8">
        @if ($appSettings->display_system_status)
            <section data-component="status-overview" class="status-overview relative">
                @if ($appSettings->show_site_name)
                    <div data-slot="masthead" class="status-overview__masthead">
                        <h1 data-slot="title" class="status-overview__title font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $siteName }}</h1>
                    </div>
                @endif

                <x-cachet::status-bar :is-heading="! $appSettings->show_site_name" />
            </section>

            <x-cachet::system-status />
        @else
            <section data-component="status-overview" class="status-overview relative">
                @if ($appSettings->show_site_name)
                    <div data-slot="masthead" class="status-overview__masthead">
                        <h1 data-slot="title" class="status-overview__title font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $siteName }}</h1>
                    </div>
                @endif

                <x-cachet::status-bar :is-heading="! $appSettings->show_site_name" />

                <x-cachet::component-groups />
            </section>
        @endif

        <x-cachet::about :show-site-name="false" />

        @if ($display_graphs)
        <x-cachet::metrics />
        @endif

        @if ($schedules->isNotEmpty())
            <x-cachet::schedules :schedules="$schedules" />
        @endif

        <div class="flex justify-end">
            <a href="{{ route('cachet.status-page.history') }}"
               data-slot="history-link"
               class="text-sm font-medium text-accent-content underline-offset-2 hover:underline">
                {{ __('status_history.view_calendar') }}
            </a>
        </div>

        <x-cachet::incident-timeline />
    </main>

    <x-cachet::footer />
</x-cachet::cachet>
