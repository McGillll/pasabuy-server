<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedActionException;
use App\Interfaces\Order\OrderRepositoryInterface;
use App\Interfaces\OrderPayment\OrderPaymentRepositoryInterface;
use App\Interfaces\OrderPayment\OrderPaymentServiceInterface;
use Illuminate\Support\Facades\DB;

class OrderPaymentService implements OrderPaymentServiceInterface
{
    public function __construct(
        protected OrderPaymentRepositoryInterface $orderPaymentRepository,
        protected OrderRepositoryInterface $orderRepository,
    ) {}

    public function listForOrder(string $orderUuid, int $userId)
    {
        $order = $this->orderRepository->findByUuid($orderUuid);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        $this->ensureOrderOwnership($order, $userId);

        return $this->orderPaymentRepository->listForOrder($order);
    }

    public function create(string $orderUuid, object $payload)
    {
        $order = $this->orderRepository->findByUuid($orderUuid);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        $this->ensureOrderOwnership($order, $payload->user_id);

        $payload->order = $order;

        return DB::transaction(function () use ($payload, $order) {
            $payment = $this->orderPaymentRepository->create($payload);
            $this->orderRepository->recalcPaymentStatus($order);

            return $payment;
        });
    }

    public function delete(string $orderUuid, string $uuid, int $userId)
    {
        $order = $this->orderRepository->findByUuid($orderUuid);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        $this->ensureOrderOwnership($order, $userId);

        $payment = $this->orderPaymentRepository->findByUuid($uuid);

        if (! $payment || $payment->order_id !== $order->id) {
            throw new NotFoundException('Payment not found.');
        }

        DB::transaction(function () use ($payment, $order) {
            $this->orderPaymentRepository->delete($payment);
            $this->orderRepository->recalcPaymentStatus($order);
        });
    }

    protected function ensureOrderOwnership(object $order, int $userId)
    {
        if ($order->pasabuySchedule->user_id !== $userId) {
            throw new UnauthorizedActionException('You do not own this order.');
        }
    }
}
