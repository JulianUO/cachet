<?php

/*
 * Native Slack notifications — runtime config is stored in the database
 * (Integrations → Slack notifications). This file only holds non-secret
 * HTTP defaults.
 */

return [
    'timeout' => (int) env('SLACK_HTTP_TIMEOUT', 10),
];
