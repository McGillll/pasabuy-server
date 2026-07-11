<?php

namespace App\Interfaces\OrderPayment;

interface OrderPaymentRepositoryInterface
{
    public function findByUuid(string $uuid);

    public function listForOrder(object $order);

    public function create(object $payload);

    public function delete(object $payment);
}
