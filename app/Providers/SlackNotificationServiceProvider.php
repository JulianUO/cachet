<?php

namespace App\Providers;

use App\Filament\Pages\Integrations\ManageSlackNotifications;
use App\Listeners\SendSlackNotification;
use Cachet\Events\Components\ComponentStatusWasChanged;
use Cachet\Events\Incidents\IncidentCreated;
use Cachet\Events\Incidents\IncidentUpdated;
use Cachet\Models\Schedule;
use Cachet\Models\Update;
use Filament\PanelRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class SlackNotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            base_path('config/slack_notifications.php'),
            'slack_notifications',
        );

        $this->app->resolving(PanelRegistry::class, function (PanelRegistry $registry): void {
            $panel = $registry->get('cachet');

            if ($panel === null) {
                return;
            }

            $panel->pages([
                ManageSlackNotifications::class,
            ]);
        });
    }

    public function boot(): void
    {
        Livewire::component(ManageSlackNotifications::class, ManageSlackNotifications::class);

        Event::listen(IncidentCreated::class, [SendSlackNotification::class, 'handleIncidentCreated']);
        Event::listen(IncidentUpdated::class, [SendSlackNotification::class, 'handleIncidentUpdated']);
        Event::listen(ComponentStatusWasChanged::class, [SendSlackNotification::class, 'handleComponentStatusChanged']);

        Update::created(function (Update $update): void {
            DB::afterCommit(function () use ($update): void {
                app(SendSlackNotification::class)->handleUpdateCreated($update->fresh(['updateable.components']) ?? $update);
            });
        });

        Schedule::created(function (Schedule $schedule): void {
            // CreateSchedule syncs components inside the same transaction — wait for commit.
            DB::afterCommit(function () use ($schedule): void {
                $fresh = $schedule->fresh(['components']) ?? $schedule;
                app(SendSlackNotification::class)->handleScheduleCreated($fresh);
            });
        });

        Schedule::updated(function (Schedule $schedule): void {
            DB::afterCommit(function () use ($schedule): void {
                app(SendSlackNotification::class)->handleScheduleUpdated($schedule);
            });
        });
    }
}
