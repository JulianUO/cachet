<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Extra SMTP transport options for Cachet mail (companion to MailSettings).
 *
 * Core MailSettings has no encryption field; this stores encryption for the
 * dashboard-managed `cachet` mailer once MailSettings::configured() is true.
 */
class MailTransportSettings extends Settings
{
    /**
     * SMTP encryption: null/empty (none), "tls", or "ssl".
     */
    public ?string $encryption = null;

    public static function group(): string
    {
        return 'mail_transport';
    }
}
