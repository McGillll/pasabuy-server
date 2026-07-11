<?php

namespace Database\Seeders;

use App\Interfaces\PasabuySchedule\PasabuyScheduleRepositoryInterface;
use App\Models\PasabuySchedule;
use App\Models\PasabuyScheduleChecklistItem;
use Illuminate\Database\Seeder;

class ChecklistSeeder extends Seeder
{
    public function run()
    {
        if (PasabuyScheduleChecklistItem::exists()) {
            return;
        }

        $repository = app(PasabuyScheduleRepositoryInterface::class);

        PasabuySchedule::whereIn('status', ['closed', 'finished'])->each(function (PasabuySchedule $schedule) use ($repository) {
            $repository->getOrCreateChecklistShareToken($schedule->uuid);
            $repository->syncChecklistOnClose($schedule);
        });
    }
}
