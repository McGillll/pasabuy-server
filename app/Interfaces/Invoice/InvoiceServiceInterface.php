<?php

namespace App\Interfaces\Invoice;

use App\Models\PasabuyOrder;

interface InvoiceServiceInterface
{
    public function buildForOrder(PasabuyOrder $order);

    public function summaryForOrder(PasabuyOrder $order);
}
