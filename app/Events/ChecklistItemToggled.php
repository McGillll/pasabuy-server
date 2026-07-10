<?php

namespace App\Events;

use App\Http\Resources\PasabuyScheduleChecklistItemResource;
use App\Models\PasabuyScheduleChecklistItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ChecklistItemToggled implements ShouldBroadcastNow
{
    public function __construct(
        public string $checklistShareToken,
        public PasabuyScheduleChecklistItem $item,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("checklist.{$this->checklistShareToken}")];
    }

    public function broadcastAs(): string
    {
        return 'checklist.item.toggled';
    }

    public function broadcastWith(): array
    {
        return (new PasabuyScheduleChecklistItemResource($this->item))->resolve();
    }
}
