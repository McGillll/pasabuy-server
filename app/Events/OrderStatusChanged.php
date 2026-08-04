<?php

namespace App\Events;

use App\Http\Resources\OrderResource;
use App\Models\PasabuyOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class OrderStatusChanged implements ShouldBroadcastNow
{
    public function __construct(
        public PasabuyOrder $order,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("order.{$this->order->uuid}")];
    }

    public function broadcastAs(): string
    {
        return 'order.status.changed';
    }

    public function broadcastWith(): array
    {
        return (new OrderResource($this->order))->resolve();
    }
}
