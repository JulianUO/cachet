<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        rescue(fn () => $this->migrator->add('slack_notifications.enabled', false));
        rescue(fn () => $this->migrator->add('slack_notifications.webhook_url', null));
        rescue(fn () => $this->migrator->add('slack_notifications.bot_token', null));
        rescue(fn () => $this->migrator->add('slack_notifications.channel', null));
        rescue(fn () => $this->migrator->add('slack_notifications.use_bot_when_webhook_set', false));
        rescue(fn () => $this->migrator->add('slack_notifications.username', 'Cachet'));
        rescue(fn () => $this->migrator->add('slack_notifications.icon_emoji', ':traffic_light:'));
        rescue(fn () => $this->migrator->add('slack_notifications.notify_incidents', true));
        rescue(fn () => $this->migrator->add('slack_notifications.notify_component_status', true));
        rescue(fn () => $this->migrator->add('slack_notifications.notify_schedules', true));
    }
};
