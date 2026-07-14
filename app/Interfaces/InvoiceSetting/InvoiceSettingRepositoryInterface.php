<?php

namespace App\Interfaces\InvoiceSetting;

interface InvoiceSettingRepositoryInterface
{
    public function getForUser(int $userId);

    public function update(object $payload, int $userId);
}
