<?php

namespace App\Interfaces\OrderPayment;

interface OrderPaymentServiceInterface
{
    public function listForOrder(string $orderUuid, int $userId);

    public function create(string $orderUuid, object $payload);

    public function delete(string $orderUuid, string $uuid, int $userId);
}
