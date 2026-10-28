<?php

namespace App\Interfaces\OrderPayment;

use App\Models\User;

interface OrderPaymentServiceInterface
{
    public function listForOrder(string $orderUuid, User $user);

    public function create(string $orderUuid, object $payload);

    public function delete(string $orderUuid, string $uuid, User $user);
}
