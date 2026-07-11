<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Interfaces\Order\OrderRepositoryInterface;
use App\Interfaces\OrderPayment\OrderPaymentRepositoryInterface;
use App\Interfaces\OrderPayment\OrderPaymentServiceInterface;

class OrderPaymentService implements OrderPaymentServiceInterface
{
    public function __construct(
        protected OrderPaymentRepositoryInterface $orderPaymentRepository,
        protected OrderRepositoryInterface $orderRepository,
    ) {}

    public function listForOrder(string $orderUuid)
    {
        $order = $this->orderRepository->findByUuid($orderUuid);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        return $this->orderPaymentRepository->listForOrder($order);
    }

    public function create(string $orderUuid, object $payload)
    {
        $order = $this->orderRepository->findByUuid($orderUuid);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        $payload->order = $order;

        return $this->orderPaymentRepository->create($payload);
    }

    public function delete(string $orderUuid, string $uuid)
    {
        $order = $this->orderRepository->findByUuid($orderUuid);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        $payment = $this->orderPaymentRepository->findByUuid($uuid);

        if (! $payment || $payment->order_id !== $order->id) {
            throw new NotFoundException('Payment not found.');
        }

        $this->orderPaymentRepository->delete($payment);
    }
}
