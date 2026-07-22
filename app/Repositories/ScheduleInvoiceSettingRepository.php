<?php

namespace App\Repositories;

use App\Interfaces\ScheduleInvoiceSetting\ScheduleInvoiceSettingRepositoryInterface;
use App\Models\PasabuySchedule;
use App\Models\PasabuyScheduleInvoiceSetting;
use App\Models\PasabuySellerInvoiceSetting;

class ScheduleInvoiceSettingRepository implements ScheduleInvoiceSettingRepositoryInterface
{
    public function findForSchedule(int $scheduleId)
    {
        return PasabuyScheduleInvoiceSetting::where('pasabuy_schedule_id', $scheduleId)->first();
    }

    public function createFromSource(PasabuySchedule $schedule, PasabuySellerInvoiceSetting $source)
    {
        return PasabuyScheduleInvoiceSetting::firstOrCreate(
            ['pasabuy_schedule_id' => $schedule->id],
            [
                'source_invoice_setting_id' => $source->id,
                'markup_threshold_amount' => $source->markup_threshold_amount,
                'markup_percentage' => $source->markup_percentage,
                'markup_fixed_amount' => $source->markup_fixed_amount,
                'due_days' => $source->due_days,
                'due_date_reference' => $source->due_date_reference,
                'interest_rate' => $source->interest_rate,
                'interest_period' => $source->interest_period,
                'interest_base' => $source->interest_base,
                'is_active' => $source->is_active,
            ],
        );
    }
}
