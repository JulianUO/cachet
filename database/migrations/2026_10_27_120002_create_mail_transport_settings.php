<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        rescue(fn () => $this->migrator->add('mail_transport.encryption', null));
    }
};
