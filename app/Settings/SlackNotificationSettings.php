<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SlackNotificationSettings extends Settings
{
    public bool $enabled = false;

    public ?string $webhook_url = null;

    public ?string $bot_token = null;

    public ?string $channel = null;

    public bool $use_bot_when_webhook_set = false;

    public string $username = 'Cachet';

    public string $icon_emoji = ':traffic_light:';

    public bool $notify_incidents = true;

    public bool $notify_component_status = true;

    public bool $notify_schedules = true;

    public static function group(): string
    {
        return 'slack_notifications';
    }
}
