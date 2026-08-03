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
        $existing = $this->findForSchedule($schedule->id);

        if ($existing) {
            return $existing;
        }

        $setting = new PasabuyScheduleInvoiceSetting;
        $setting->pasabuy_schedule_id = $schedule->id;
        $setting->source_invoice_setting_id = $source->id;
        $setting->markup_threshold_amount = $source->markup_threshold_amount;
        $setting->markup_percentage = $source->markup_percentage;
        $setting->markup_fixed_amount = $source->markup_fixed_amount;
        $setting->handling_fee = $source->handling_fee;
        $setting->interest_rate = $source->interest_rate;
        $setting->interest_period = $source->interest_period;
        $setting->interest_base = $source->interest_base;
        $setting->is_active = $source->is_active;
        $setting->save();

        return $setting;
    }
}
