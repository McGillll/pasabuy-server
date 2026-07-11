<?php

namespace App\Events;

use App\Http\Resources\PasabuyScheduleChecklistItemResource;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Collection;

class ChecklistRegenerated implements ShouldBroadcastNow
{
    public function __construct(
        public string $checklistShareToken,
        public Collection $items,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("checklist.{$this->checklistShareToken}")];
    }

    public function broadcastAs(): string
    {
        return 'checklist.regenerated';
    }

    public function broadcastWith(): array
    {
        return ['items' => PasabuyScheduleChecklistItemResource::collection($this->items)->resolve()];
    }
}
