<?php

namespace App\Repositories;

use App\Interfaces\OrderPayment\OrderPaymentRepositoryInterface;
use App\Models\OrderPayment;

class OrderPaymentRepository implements OrderPaymentRepositoryInterface
{
    public function findByUuid(string $uuid)
    {
        return OrderPayment::with('recordedBy')->where('uuid', $uuid)->first();
    }

    public function listForOrder(object $order)
    {
        return $order->payments()->with('recordedBy')->latest('paid_at')->get();
    }

    public function create(object $payload)
    {
        $payment = new OrderPayment;
        $payment->order_id = $payload->order->id;
        $payment->recorded_by_user_id = $payload->recorded_by_user_id;
        $payment->amount = $payload->amount;
        $payment->method = $payload->method;
        $payment->reference_number = $payload->reference_number ?? null;
        $payment->notes = $payload->notes ?? null;
        $payment->paid_at = $payload->paid_at;
        $payment->save();

        return $payment->fresh('recordedBy');
    }

    public function delete(object $payment)
    {
        $payment->delete();
    }
}
