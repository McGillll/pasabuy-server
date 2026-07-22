<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;
use Illuminate\Support\Carbon;

class PasabuyOrderFilter extends ModelFilter
{
    public $relations = [];

    public function search($query)
    {
        return $this->where(function ($q) use ($query) {
            $q->where('customer_name', 'like', "%{$query}%")
                ->orWhere('customer_contact', 'like', "%{$query}%")
                ->orWhere('order_no', 'like', "%{$query}%");
        });
    }

    public function status($value)
    {
        return $this->where('status', $value);
    }

    public function paymentStatus($value)
    {
        return $this->where('payment_status', $value);
    }

    public function deliveryStatus($value)
    {
        return $this->where('delivery_status', $value);
    }

    public function dateFrom($value)
    {
        return $this->where('created_at', '>=', Carbon::parse($value)->startOfDay());
    }

    public function dateTo($value)
    {
        return $this->where('created_at', '<=', Carbon::parse($value)->endOfDay());
    }

    public function scheduleUuid($value)
    {
        return $this->whereHas('pasabuySchedule', function ($query) use ($value) {
            $query->where('uuid', $value);
        });
    }

    public function scheduleTitle($value)
    {
        return $this->whereHas('pasabuySchedule', function ($query) use ($value) {
            $query->where('title', 'like', "%{$value}%");
        });
    }
}
