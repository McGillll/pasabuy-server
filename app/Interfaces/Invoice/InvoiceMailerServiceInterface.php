<?php

namespace App\Interfaces\Invoice;

use App\Models\PasabuyOrder;

interface InvoiceMailerServiceInterface
{
    public function sendIfDue(PasabuyOrder $order, string $trigger);

    public function sendOverdueReminderIfDue(PasabuyOrder $order, array $invoice);
}
