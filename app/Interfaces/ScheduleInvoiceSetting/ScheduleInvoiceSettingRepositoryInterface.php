<?php

namespace App\Interfaces\ScheduleInvoiceSetting;

use App\Models\PasabuySchedule;
use App\Models\PasabuySellerInvoiceSetting;

interface ScheduleInvoiceSettingRepositoryInterface
{
    public function findForSchedule(int $scheduleId);

    public function createFromSource(PasabuySchedule $schedule, PasabuySellerInvoiceSetting $source);
}
