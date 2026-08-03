<?php

namespace App\Interfaces\Invoice;

use App\Models\PasabuyOrder;

interface InvoiceMailerServiceInterface
{
    public function sendIfDue(PasabuyOrder $order);

    public function sendOverdueReminderIfDue(PasabuyOrder $order, array $invoice);
}
