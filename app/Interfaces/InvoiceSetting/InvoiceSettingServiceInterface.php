<?php

namespace App\Interfaces\InvoiceSetting;

interface InvoiceSettingServiceInterface
{
    public function getForUser(int $userId);

    public function update(object $payload, int $userId);
}
