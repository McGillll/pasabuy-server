<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Interfaces\Settings\SettingsRepositoryInterface;
use App\Interfaces\Settings\SettingsServiceInterface;

class SettingsService implements SettingsServiceInterface
{
    public function __construct(
        protected SettingsRepositoryInterface $settingsRepository,
    ) {}

    public function get()
    {
        $setting = $this->settingsRepository->get();

        if (! $setting) {
            throw new NotFoundException('Settings not found.');
        }

        return $setting;
    }

    public function update(object $payload)
    {
        return $this->settingsRepository->update($payload);
    }
}
