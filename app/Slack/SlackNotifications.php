<?php

namespace App\Slack;

use App\Settings\SlackNotificationSettings;
use Throwable;

class SlackNotifications
{
    public static function settings(): SlackNotificationSettings
    {
        return app(SlackNotificationSettings::class);
    }

    public static function enabled(): bool
    {
        try {
            return self::settings()->enabled && self::hasDeliveryMethod();
        } catch (Throwable) {
            return false;
        }
    }

    public static function hasDeliveryMethod(): bool
    {
        return self::webhookConfigured() || self::botConfigured();
    }

    public static function webhookUrl(): ?string
    {
        $url = trim((string) (self::settings()->webhook_url ?? ''));

        return $url !== '' ? $url : null;
    }

    public static function botToken(): ?string
    {
        $token = trim((string) (self::settings()->bot_token ?? ''));

        return $token !== '' ? $token : null;
    }

    public static function channel(): ?string
    {
        $channel = trim((string) (self::settings()->channel ?? ''));

        return $channel !== '' ? $channel : null;
    }

    public static function webhookConfigured(): bool
    {
        $url = self::webhookUrl();

        return $url !== null && str_starts_with($url, 'https://');
    }

    public static function botConfigured(): bool
    {
        $token = self::botToken();
        $channel = self::channel();

        return $token !== null && $channel !== null && str_starts_with($token, 'xoxb-');
    }

    public static function shouldNotifyIncidents(): bool
    {
        return (bool) self::settings()->notify_incidents;
    }

    public static function shouldNotifyComponentStatus(): bool
    {
        return (bool) self::settings()->notify_component_status;
    }

    public static function shouldNotifySchedules(): bool
    {
        return (bool) self::settings()->notify_schedules;
    }

    public static function username(): string
    {
        $username = trim((string) self::settings()->username);

        return $username !== '' ? $username : 'Cachet';
    }

    public static function iconEmoji(): string
    {
        $emoji = trim((string) self::settings()->icon_emoji);

        return $emoji !== '' ? $emoji : ':traffic_light:';
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
            ! self::webhookConfigured() || (bool) self::settings()->use_bot_when_webhook_set
        )) {
            $methods[] = 'bot_token';
        }

        return $methods;
    }

    /**
     * Public status for the admin UI — never includes secret values.
     *
     * @return array<string, mixed>
     */
    public static function status(): array
    {
        try {
            $settings = self::settings();
        } catch (Throwable) {
            return [
                'enabled' => false,
                'ready' => false,
                'webhook_configured' => false,
                'bot_configured' => false,
                'channel' => null,
                'username' => 'Cachet',
                'delivery_methods' => [],
                'events' => [
                    'incidents' => true,
                    'component_status' => true,
                    'schedules' => true,
                ],
                'last_failure' => SlackDeliveryLog::lastFailure(),
                'last_success_at' => SlackDeliveryLog::lastSuccessAt(),
                'rss_fallback' => route('cachet.slack-rss'),
            ];
        }

        return [
            'enabled' => (bool) $settings->enabled,
            'ready' => self::enabled(),
            'webhook_configured' => self::webhookConfigured(),
            'bot_configured' => self::botConfigured(),
            'channel' => self::channel(),
            'username' => self::username(),
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
