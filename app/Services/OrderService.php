<?php

namespace App\Services;

use App\Exceptions\InvalidPayloadException;
use App\Exceptions\NotFoundException;
use App\Interfaces\Order\OrderRepositoryInterface;
use App\Interfaces\Order\OrderServiceInterface;
use App\Interfaces\PasabuySchedule\PasabuyScheduleRepositoryInterface;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderService implements OrderServiceInterface
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepository,
        protected PasabuyScheduleRepositoryInterface $pasabuyScheduleRepository,
    ) {}

    public function updateStatus(string $uuid, object $payload)
    {
        $order = $this->orderRepository->findByUuid($uuid);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        return $this->orderRepository->updateStatus($uuid, $payload);
    }

    public function createForSchedule(string $scheduleUuid, object $payload)
    {
        $schedule = $this->pasabuyScheduleRepository->findByUuid($scheduleUuid);

        if (! $schedule) {
            throw new NotFoundException('Pasabuy schedule not found.');
        }

        $this->ensureScheduleIsOpen($schedule);

        if ($this->orderRepository->findActiveForSchedule($payload->user->id, $schedule->id)) {
            throw new InvalidPayloadException('You already have an order for this pasabuy. Edit your existing order instead.');
        }

        $resolvedItems = $this->resolveItems($schedule, $payload->items);

        return DB::transaction(function () use ($schedule, $payload, $resolvedItems) {
            $order = $this->orderRepository->create((object) [
                'pasabuy_schedule_id' => $schedule->id,
                'user_id' => $payload->user->id,
                'customer_name' => $payload->user->fullName(),
                'customer_contact' => $payload->user->contact_number,
            ]);

            return $this->orderRepository->addItems($order, (object) ['items' => $resolvedItems]);
        });
    }

    public function updateItemsForCustomer(string $orderUuid, object $payload)
    {
        $order = $this->orderRepository->findOwnedByUuid($orderUuid, $payload->user->id);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        if (! in_array($order->status, Order::EDITABLE_STATUSES)) {
            throw new InvalidPayloadException('This order can no longer be edited.');
        }

        $schedule = $this->pasabuyScheduleRepository->findByUuid($order->pasabuySchedule->uuid);

        $this->ensureScheduleIsOpen($schedule);

        $resolvedItems = $this->resolveItems($schedule, $payload->items, $order->id);

        return DB::transaction(function () use ($order, $resolvedItems) {
            return $this->orderRepository->replaceItems($order, (object) ['items' => $resolvedItems]);
        });
    }

    public function myOrders(object $payload)
    {
        return $this->orderRepository->myOrders($payload->user->id, $payload);
    }

    public function findOwnedByUuid(string $uuid, object $payload)
    {
        $order = $this->orderRepository->findOwnedByUuid($uuid, $payload->user->id);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        return $order;
    }

    protected function ensureScheduleIsOpen($schedule)
    {
        $isOpen = $schedule->status === 'open' && (! $schedule->cutoff_at || $schedule->cutoff_at->isFuture());

        if (! $isOpen) {
            throw new InvalidPayloadException('This pasabuy is closed for orders.');
        }
    }

    protected function resolveItems($schedule, array $items, ?int $excludeOrderId = null)
    {
        $scheduleProductsByProductUuid = $schedule->scheduleProducts->keyBy(fn ($scheduleProduct) => $scheduleProduct->product->uuid);

        return collect($items)->map(function ($item) use ($scheduleProductsByProductUuid, $schedule, $excludeOrderId) {
            $scheduleProduct = $scheduleProductsByProductUuid->get($item['product_uuid']);

            if (! $scheduleProduct) {
                throw new InvalidPayloadException("Product {$item['product_uuid']} is not available in this pasabuy.");
            }

            if ($scheduleProduct->max_quantity !== null) {
                $alreadyOrdered = $this->orderRepository->orderedQuantity($schedule->id, $scheduleProduct->product_id, $excludeOrderId);

                if ($alreadyOrdered + $item['quantity'] > $scheduleProduct->max_quantity) {
                    $remaining = max(0, $scheduleProduct->max_quantity - $alreadyOrdered);
                    throw new InvalidPayloadException("Only {$remaining} of \"{$scheduleProduct->product->name}\" remaining.");
                }
            }

            return [
                'product_id' => $scheduleProduct->product_id,
                'product_name' => $scheduleProduct->product->name,
                'product_price' => $scheduleProduct->price,
                'quantity' => $item['quantity'],
            ];
        })->all();
    }
}
