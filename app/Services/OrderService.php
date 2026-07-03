<?php

namespace App\Services;

use App\Exceptions\InvalidPayloadException;
use App\Exceptions\NotFoundException;
use App\Interfaces\Order\OrderRepositoryInterface;
use App\Interfaces\Order\OrderServiceInterface;
use App\Interfaces\PasabuySchedule\PasabuyScheduleRepositoryInterface;
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

        $isOpen = $schedule->status === 'open' && (! $schedule->cutoff_at || $schedule->cutoff_at->isFuture());

        if (! $isOpen) {
            throw new InvalidPayloadException('This pasabuy is closed for orders.');
        }

        $scheduleProductsByProductUuid = $schedule->scheduleProducts->keyBy(fn ($scheduleProduct) => $scheduleProduct->product->uuid);

        $resolvedItems = collect($payload->items)->map(function ($item) use ($scheduleProductsByProductUuid, $schedule) {
            $scheduleProduct = $scheduleProductsByProductUuid->get($item['product_uuid']);

            if (! $scheduleProduct) {
                throw new InvalidPayloadException("Product {$item['product_uuid']} is not available in this pasabuy.");
            }

            if ($scheduleProduct->max_quantity !== null) {
                $alreadyOrdered = $this->orderRepository->orderedQuantity($schedule->id, $scheduleProduct->product_id);

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

        return DB::transaction(function () use ($schedule, $payload, $resolvedItems) {
            $order = $this->orderRepository->create((object) [
                'pasabuy_schedule_id' => $schedule->id,
                'customer_name' => $payload->customer_name,
                'customer_contact' => $payload->customer_contact,
            ]);

            return $this->orderRepository->addItems($order, (object) ['items' => $resolvedItems]);
        });
    }
}
