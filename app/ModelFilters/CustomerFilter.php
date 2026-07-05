<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class CustomerFilter extends ModelFilter
{
    public $relations = [];

    public function search($query)
    {
        return $this->where(function ($q) use ($query) {
            $q->where('first_name', 'like', "%{$query}%")
                ->orWhere('last_name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('contact_number', 'like', "%{$query}%");
        });
    }

    public function isApproved($value)
    {
        return $value ? $this->whereNotNull('approved_at') : $this->whereNull('approved_at');
    }
}
