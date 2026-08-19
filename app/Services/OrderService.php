<?php

namespace App\Services;

use App\Events\OrderStatusChanged;
use App\Exceptions\InvalidPayloadException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedActionException;
use App\Interfaces\Invoice\InvoiceServiceInterface;
use App\Interfaces\Order\OrderRepositoryInterface;
use App\Interfaces\Order\OrderServiceInterface;
use App\Interfaces\PasabuySchedule\PasabuyScheduleRepositoryInterface;
use App\Interfaces\Product\ProductRepositoryInterface;
use App\Models\PasabuyOrder;
use Illuminate\Support\Facades\DB;

class OrderService implements OrderServiceInterface
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepository,
        protected PasabuyScheduleRepositoryInterface $pasabuyScheduleRepository,
        protected ProductRepositoryInterface $productRepository,
        protected InvoiceServiceInterface $invoiceService,
    ) {}

    public function all(object $payload)
    {
        $orders = $this->orderRepository->all($payload);

        $this->attachInvoiceSummary($orders->items());

        return $orders;
    }

    public function updateStatus(string $uuid, object $payload)
    {
        $order = $this->orderRepository->findByUuid($uuid);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        $this->ensureOrderOwnership($order, $payload->user_id);

        $updated = $this->orderRepository->updateStatus($uuid, $payload);

        broadcast(new OrderStatusChanged($updated));

        return $updated;
    }

    public function updateDeliveryStatus(string $uuid, object $payload)
    {
        $order = $this->orderRepository->findByUuid($uuid);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        $this->ensureOrderOwnership($order, $payload->user_id);

        $updated = $this->orderRepository->updateDeliveryStatus($uuid, $payload);

        broadcast(new OrderStatusChanged($updated));

        return $updated;
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

        if ($payload->user->getReceivable() > 0) {
            throw new InvalidPayloadException('You have an unpaid order pending. Please settle it before placing a new order.');
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

    public function createForSeller(string $scheduleUuid, object $payload)
    {
        $schedule = $this->pasabuyScheduleRepository->findByUuid($scheduleUuid);

        if (! $schedule) {
            throw new NotFoundException('Pasabuy schedule not found.');
        }

        if ($schedule->user_id !== $payload->user_id) {
            throw new UnauthorizedActionException('You do not own this pasabuy schedule.');
        }

        if ($schedule->status === 'finished') {
            throw new InvalidPayloadException('This pasabuy is finished and no longer accepts new orders.');
        }

        $resolvedItems = $this->resolveItems($schedule, $payload->items);

        return DB::transaction(function () use ($schedule, $payload, $resolvedItems) {
            $order = $this->orderRepository->createForSeller((object) [
                'pasabuy_schedule_id' => $schedule->id,
                'customer_name' => $payload->customer_name,
                'customer_contact' => $payload->customer_contact ?? null,
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

        if (! in_array($order->status, PasabuyOrder::EDITABLE_STATUSES)) {
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
        $orders = $this->orderRepository->myOrders($payload->user->id, $payload);

        $this->attachInvoiceSummary($orders->items());

        return $orders;
    }

    public function findOwnedByUuid(string $uuid, object $payload)
    {
        $order = $this->orderRepository->findOwnedByUuid($uuid, $payload->user->id);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        return $order;
    }

    public function findOwnedBySellerUuid(string $uuid, object $payload)
    {
        $order = $this->orderRepository->findByUuid($uuid);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        $this->ensureOrderOwnership($order, $payload->user_id);

        return $order;
    }

    public function cancelForCustomer(string $uuid, object $payload)
    {
        $order = $this->orderRepository->findOwnedByUuid($uuid, $payload->user->id);

        if (! $order) {
            throw new NotFoundException('Order not found.');
        }

        if (! in_array($order->status, PasabuyOrder::EDITABLE_STATUSES)) {
            throw new InvalidPayloadException('This order can no longer be cancelled.');
        }

        $schedule = $this->pasabuyScheduleRepository->findByUuid($order->pasabuySchedule->uuid);

        $this->ensureScheduleIsOpen($schedule);

        return $this->orderRepository->cancel($order);
    }

    public function recalculateForScheduleProduct(object $scheduleProduct)
    {
        $items = $this->orderRepository->findLiveItemsForScheduleProduct($scheduleProduct->id);

        $summary = (object) ['auto_applied' => 0, 'bonus_applied' => 0, 'out_of_stock' => 0];

        DB::transaction(function () use ($items, $scheduleProduct, $summary) {
            foreach ($items as $item) {
                if ($scheduleProduct->product_status === 'out_of_stock') {
                    $this->orderRepository->applyResolvedPrice($item, null, null, 0);
                    $summary->out_of_stock++;

                    continue;
                }

                $bonusQuantity = $scheduleProduct->product_status === 'b1t1' ? $item->quantity : 0;

                $this->orderRepository->applyResolvedPrice($item, (float) $scheduleProduct->actual_price, null, $bonusQuantity);
                $summary->auto_applied++;

                if ($bonusQuantity > 0) {
                    $summary->bonus_applied++;
                }
            }
        });

        return $summary;
    }

    public function updateItemsForSeller(string $scheduleUuid, string $orderUuid, object $payload)
    {
        $schedule = $this->pasabuyScheduleRepository->findByUuid($scheduleUuid);

        if (! $schedule) {
            throw new NotFoundException('Pasabuy schedule not found.');
        }

        if ($schedule->user_id !== $payload->user_id) {
            throw new UnauthorizedActionException('You do not own this pasabuy schedule.');
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

    public function attachInvoiceSummary(array $orders)
    {
        foreach ($orders as $order) {
            $summary = $this->invoiceService->summaryForOrder($order);
            $order->amount_paid = $summary['amount_paid'];
            $order->amount_due = $summary['balance_due'];
            $order->due_date = $summary['due_date'];
            $order->is_overdue = $summary['is_overdue'];
        }
    }

    protected function ensureOrderOwnership(PasabuyOrder $order, int $userId)
    {
        if ($order->pasabuySchedule->user_id !== $userId) {
            throw new UnauthorizedActionException('You do not own this order.');
        }
    }

    protected function ensureScheduleIsOpen($schedule)
    {
        if (! $schedule->is_open) {
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

            $isOutOfStock = $scheduleProduct->product_status === 'out_of_stock';

            return [
                'pasabuy_schedule_product_id' => $scheduleProduct->id,
                'product_name' => $product->name,
                'estimated_price' => $product->default_price,
                'actual_price' => $isOutOfStock ? null : $scheduleProduct->actual_price,
                'quantity' => $item['quantity'],
                'bonus_quantity' => ! $isOutOfStock && $scheduleProduct->product_status === 'b1t1' ? $item['quantity'] : 0,
                'is_out_of_stock' => $isOutOfStock,
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

            $isOutOfStock = $scheduleProduct->product_status === 'out_of_stock';

            return [
                'pasabuy_schedule_product_id' => $scheduleProduct->id,
                'product_name' => $product->name,
                'estimated_price' => $product->default_price,
                'actual_price' => ($isOutOfStock || ! $scheduleProduct->price_confirmed_at) ? null : $scheduleProduct->actual_price,
                'quantity' => $item['quantity'],
                'bonus_quantity' => ! $isOutOfStock && $scheduleProduct->product_status === 'b1t1' ? $item['quantity'] : 0,
                'is_out_of_stock' => $isOutOfStock,
            ];
        })->all();
    }
}
