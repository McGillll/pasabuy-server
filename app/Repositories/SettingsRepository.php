<?php

namespace App\Repositories;

use App\Interfaces\Settings\SettingsRepositoryInterface;
use App\Models\PasabuySetting;
use App\Traits\InteractsWithCache;

class SettingsRepository implements SettingsRepositoryInterface
{
    use InteractsWithCache;

    public function get()
    {
        return PasabuySetting::first();
    }

    public function update(object $payload)
    {
        $setting = $this->get();

        $setting->business_name = $payload->business_name;
        $setting->contact_email = $payload->contact_email ?? null;
        $setting->contact_phone = $payload->contact_phone ?? null;
        $setting->address = $payload->address ?? null;
        $setting->save();

        $this->bumpCacheVersion('settings');

        return $setting;
    }
}
