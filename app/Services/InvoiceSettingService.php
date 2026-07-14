<?php

namespace App\Services;

use App\Interfaces\InvoiceSetting\InvoiceSettingRepositoryInterface;
use App\Interfaces\InvoiceSetting\InvoiceSettingServiceInterface;

class InvoiceSettingService implements InvoiceSettingServiceInterface
{
    public function __construct(
        protected InvoiceSettingRepositoryInterface $invoiceSettingRepository,
    ) {}

    public function getForUser(int $userId)
    {
        return $this->invoiceSettingRepository->getForUser($userId);
    }

    public function update(object $payload, int $userId)
    {
        return $this->invoiceSettingRepository->update($payload, $userId);
    }
}
