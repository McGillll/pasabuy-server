<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class CustomerFilter extends ModelFilter
{
    public $relations = [];

    public function search($query)
    {
        return $this->where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%");
        });
    }

    public function isApproved($value)
    {
        return $this->where('is_approved', $value);
    }
}
