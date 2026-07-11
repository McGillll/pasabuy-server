<?php

namespace App\Interfaces\Settings;

interface SettingsRepositoryInterface
{
    public function get();

    public function update(object $payload);
}
