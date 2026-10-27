<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Mail;

use App\Settings\MailTransportSettings;
use Cachet\Settings\MailSettings;

/**
 * Ensure dashboard mail settings (including encryption) override Laravel mail config.
 */
class AppliesMailTransportSettings
{
    public static function apply(): void
    {
        rescue(function (): void {
            $settings = app(MailSettings::class);
            $transport = app(MailTransportSettings::class);

            if ($settings->from_address !== null) {
                config()->set('mail.from.address', $settings->from_address);
            }

            if ($settings->from_name !== null) {
                config()->set('mail.from.name', $settings->from_name);
            }

            if (! $settings->configured()) {
                return;
            }

            config()->set('mail.mailers.cachet', self::mailerConfig($settings, $transport));
            config()->set('mail.default', 'cachet');
        }, report: false);
    }

    /**
     * @return array<string, mixed>
     */
    public static function mailerConfig(?MailSettings $settings = null, ?MailTransportSettings $transport = null): array
    {
        $settings ??= app(MailSettings::class);
        $transport ??= app(MailTransportSettings::class);

        $config = $settings->toMailerConfig();

        if (($config['transport'] ?? null) === 'smtp') {
            $config['encryption'] = filled($transport->encryption) ? $transport->encryption : null;
        }

        return $config;
    }
}
