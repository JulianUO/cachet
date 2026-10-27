<?php

/*
 * Native Slack notifications for Cachet status events.
 * Preferred over /slack.rss — configure via environment variables.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Enable native Slack notifications
    |--------------------------------------------------------------------------
    */
    'enabled' => (bool) env('SLACK_NOTIFICATIONS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Incoming Webhook (preferred when set)
    |--------------------------------------------------------------------------
    |
    | Create an Incoming Webhook in Slack and paste the URL here.
    | When set, Cachet POSTs JSON payloads to this URL.
    |
    */
    'webhook_url' => env('SLACK_WEBHOOK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Bot token + channel (alternative / fallback)
    |--------------------------------------------------------------------------
    |
    | Use a Slack app Bot User OAuth Token (xoxb-...) with chat:write,
    | and a channel ID (C…) or name (#status). Used when webhook_url is empty,
    | or in addition if SLACK_USE_BOT_WHEN_WEBHOOK_SET=true.
    |
    */
    'bot_token' => env('SLACK_BOT_TOKEN'),
    'channel' => env('SLACK_CHANNEL'),
    'use_bot_when_webhook_set' => (bool) env('SLACK_USE_BOT_WHEN_WEBHOOK_SET', false),

    /*
    |--------------------------------------------------------------------------
    | Message appearance
    |--------------------------------------------------------------------------
    */
    'username' => env('SLACK_USERNAME', 'Cachet'),
    'icon_emoji' => env('SLACK_ICON_EMOJI', ':traffic_light:'),

    /*
    |--------------------------------------------------------------------------
    | Which events to send
    |--------------------------------------------------------------------------
    */
    'events' => [
        'incidents' => (bool) env('SLACK_NOTIFY_INCIDENTS', true),
        'component_status' => (bool) env('SLACK_NOTIFY_COMPONENT_STATUS', true),
        'schedules' => (bool) env('SLACK_NOTIFY_SCHEDULES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('SLACK_HTTP_TIMEOUT', 10),
];
