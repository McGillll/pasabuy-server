<?php

namespace App\Services;

use App\Exceptions\InvalidPayloadException;
use App\Exceptions\NotFoundException;
use App\Interfaces\Order\OrderRepositoryInterface;
use App\Interfaces\Order\OrderServiceInterface;
use App\Interfaces\PasabuySchedule\PasabuyScheduleRepositoryInterface;
use App\Interfaces\Product\ProductRepositoryInterface;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderService implements OrderServiceInterface
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepository,
        protected PasabuyScheduleRepositoryInterface $pasabuyScheduleRepository,
        protected ProductRepositoryInterface $productRepository,
    ) {}

    public function updateStatus(string $uuid, object $payload)
    {
        $order = $this->orderRepository->findByUuid($uuid);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        if ($payload->status === 'confirmed' && $order->status === 'needs_confirmation') {
            throw new InvalidPayloadException('This order still has items awaiting the customer\'s confirmation.');
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

        $resolvedItems = $this->resolveItems($schedule, $payload->items);

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

    public function cancelForCustomer(string $uuid, object $payload)
    {
        $order = $this->orderRepository->findOwnedByUuid($uuid, $payload->user->id);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        if (! in_array($order->status, Order::CONFIRMABLE_STATUSES)) {
            throw new InvalidPayloadException('This order can no longer be cancelled.');
        }

        return $this->orderRepository->cancel($order);
    }

    public function recalculateForScheduleProduct(object $scheduleProduct)
    {
        $items = $this->orderRepository->findLiveItemsForProduct($scheduleProduct->pasabuy_schedule_id, $scheduleProduct->product_id);

        $summary = (object) ['auto_applied' => 0, 'needs_confirmation' => 0, 'bonus_applied' => 0, 'out_of_stock' => 0];

        DB::transaction(function () use ($items, $scheduleProduct, $summary) {
            foreach ($items as $item) {
                if ($scheduleProduct->product_status === 'out_of_stock') {
                    $this->orderRepository->markItemOutOfStock($item);
                    $summary->out_of_stock++;

                    continue;
                }

                $actualPrice = (float) $scheduleProduct->actual_price;

                if ($actualPrice <= (float) $item->estimated_price) {
                    $this->orderRepository->applyResolvedPrice($item, $actualPrice);
                    $summary->auto_applied++;

                    continue;
                }

                if ($item->auto_buy_if_under_max && $item->max_price !== null && $actualPrice <= (float) $item->max_price) {
                    $this->orderRepository->applyResolvedPrice($item, $actualPrice);
                    $summary->auto_applied++;

                    continue;
                }

                $this->orderRepository->flagNeedsConfirmation($item, $actualPrice);
                $summary->needs_confirmation++;
            }

            $orders = $items->pluck('order')->unique('id');

            foreach ($orders as $order) {
                if ($scheduleProduct->product_status === 'b1t1') {
                    if ($this->orderRepository->addOrUpdateBonusItem($order, $scheduleProduct)) {
                        $summary->bonus_applied++;
                    }
                } else {
                    $this->orderRepository->removeBonusItem($order, $scheduleProduct->product_id);
                }
            }
        });

        return $summary;
    }

    public function resolveItem(string $orderUuid, string $itemUuid, object $payload)
    {
        $order = $this->orderRepository->findOwnedByUuid($orderUuid, $payload->user->id);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        if ($order->status !== 'needs_confirmation') {
            throw new InvalidPayloadException('This order has no pending confirmations.');
        }

        $item = $order->items->firstWhere('uuid', $itemUuid);

        if (! $item || $item->confirmation_status !== 'pending') {
            throw new NotFoundException('Order item not found or not pending confirmation.');
        }

        if ($payload->action === 'remove') {
            $this->orderRepository->markItemRemoved($item);
        } else {
            $quantity = $payload->quantity ?? $item->quantity;
            $scheduleProduct = $this->pasabuyScheduleRepository->findScheduleProduct($order->pasabuy_schedule_id, $item->product_id);

            $this->orderRepository->applyResolvedPrice($item, (float) $scheduleProduct->actual_price, $quantity);
        }

        return $this->orderRepository->revertToPendingIfResolved($order);
    }

    public function updateItemsForSeller(string $scheduleUuid, string $orderUuid, object $payload)
    {
        $schedule = $this->pasabuyScheduleRepository->findByUuid($scheduleUuid);

        if (! $schedule) {
            throw new NotFoundException('Pasabuy schedule not found.');
        }

        if ($schedule->status !== 'closed') {
            throw new InvalidPayloadException('Order items can only be edited by the seller while the schedule is closed.');
        }

        $order = $this->orderRepository->findByUuidForSchedule($orderUuid, $schedule->id);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        $resolvedItems = $this->resolveSellerItems($schedule, $payload->items);

        return DB::transaction(function () use ($order, $resolvedItems) {
            return $this->orderRepository->replaceItems($order, (object) ['items' => $resolvedItems]);
        });
    }

    protected function ensureScheduleIsOpen($schedule)
    {
        $isOpen = $schedule->status === 'open' && (! $schedule->cutoff_at || $schedule->cutoff_at->isFuture());

        if (! $isOpen) {
            throw new InvalidPayloadException('This pasabuy is closed for orders.');
        }
    }

    protected function resolveItems($schedule, array $items)
    {
        return collect($items)->map(function ($item) use ($schedule) {
            $product = $this->productRepository->findByUuid($item['product_uuid']);

            if (! $product || ! $product->is_active) {
                throw new InvalidPayloadException("Product {$item['product_uuid']} is not available.");
            }

            $scheduleProduct = $this->pasabuyScheduleRepository->findOrCreateScheduleProduct($schedule, $product);

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'estimated_price' => $product->default_price,
                'actual_price' => $scheduleProduct->actual_price,
                'confirmation_status' => $scheduleProduct->product_status === 'out_of_stock' ? 'pending' : 'none',
                'quantity' => $item['quantity'],
                'max_price' => $item['max_price'] ?? null,
                'auto_buy_if_under_max' => $item['auto_buy_if_under_max'] ?? false,
            ];
        })->all();
    }

    protected function resolveSellerItems($schedule, array $items)
    {
        return collect($items)->map(function ($item) use ($schedule) {
            $product = $this->productRepository->findByUuid($item['product_uuid']);

            if (! $product || ! $product->is_active) {
                throw new InvalidPayloadException("Product {$item['product_uuid']} is not available.");
            }

            $scheduleProduct = $this->pasabuyScheduleRepository->findOrCreateScheduleProduct($schedule, $product);

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'estimated_price' => $product->default_price,
                'actual_price' => $scheduleProduct->price_confirmed_at ? $scheduleProduct->actual_price : null,
                'confirmation_status' => $scheduleProduct->product_status === 'out_of_stock' ? 'pending' : 'none',
                'quantity' => $item['quantity'],
                'max_price' => null,
                'auto_buy_if_under_max' => false,
            ];
        })->all();
    }
}
