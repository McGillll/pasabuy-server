<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class OrderFilter extends ModelFilter
{
    public $relations = [];

    public function search($query)
    {
        return $this->where(function ($q) use ($query) {
            $q->where('customer_name', 'like', "%{$query}%")
                ->orWhere('customer_contact', 'like', "%{$query}%");
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
}
