<?php

namespace App\Slack;

class SlackNotifications
{
    public static function enabled(): bool
    {
        return (bool) config('slack_notifications.enabled')
            && self::hasDeliveryMethod();
    }

    public static function hasDeliveryMethod(): bool
    {
        return self::webhookConfigured() || self::botConfigured();
    }

    public static function webhookConfigured(): bool
    {
        $url = (string) config('slack_notifications.webhook_url');

        return $url !== '' && str_starts_with($url, 'https://');
    }

    public static function botConfigured(): bool
    {
        $token = (string) config('slack_notifications.bot_token');
        $channel = (string) config('slack_notifications.channel');

        return $token !== '' && $channel !== '' && str_starts_with($token, 'xoxb-');
    }

    public static function shouldNotifyIncidents(): bool
    {
        return (bool) config('slack_notifications.events.incidents', true);
    }

    public static function shouldNotifyComponentStatus(): bool
    {
        return (bool) config('slack_notifications.events.component_status', true);
    }

    public static function shouldNotifySchedules(): bool
    {
        return (bool) config('slack_notifications.events.schedules', true);
    }

    /**
     * @return list<string>
     */
    public static function deliveryMethods(): array
    {
        $methods = [];

        if (self::webhookConfigured()) {
            $methods[] = 'incoming_webhook';
        }

        if (self::botConfigured() && (
            ! self::webhookConfigured() || (bool) config('slack_notifications.use_bot_when_webhook_set')
        )) {
            $methods[] = 'bot_token';
        }

        return $methods;
    }

    /**
     * @return array<string, mixed>
     */
    public static function status(): array
    {
        return [
            'enabled' => (bool) config('slack_notifications.enabled'),
            'ready' => self::enabled(),
            'webhook_configured' => self::webhookConfigured(),
            'bot_configured' => self::botConfigured(),
            'channel' => (string) config('slack_notifications.channel'),
            'username' => (string) config('slack_notifications.username'),
            'delivery_methods' => self::deliveryMethods(),
            'events' => [
                'incidents' => self::shouldNotifyIncidents(),
                'component_status' => self::shouldNotifyComponentStatus(),
                'schedules' => self::shouldNotifySchedules(),
            ],
            'last_failure' => SlackDeliveryLog::lastFailure(),
            'last_success_at' => SlackDeliveryLog::lastSuccessAt(),
            'rss_fallback' => route('cachet.slack-rss'),
        ];
    }
}
