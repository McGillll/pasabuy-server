<?php

namespace App\Interfaces\Settings;

interface SettingsServiceInterface
{
    public function get();

    public function update(object $payload);
}
