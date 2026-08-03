<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class PasabuyProductFilter extends ModelFilter
{
    /**
    * Related Models that have ModelFilters as well as the method on the ModelFilter
    * As [relationMethod => [input_key1, input_key2]].
    *
    * @var array
    */
    public $relations = [];

    public function search($query)
    {
        return $this->where('name', 'like', "%{$query}%");
    }

    public function categoryUuid($uuid)
    {
        return $this->whereHas('category', function ($query) use ($uuid) {
            $query->where('uuid', $uuid);
        });
    }

    public function status($status)
    {
        return $this->where('default_product_status', $status);
    }

    public function priceMin($price)
    {
        return $this->where('default_price', '>=', $price);
    }

    public function priceMax($price)
    {
        return $this->where('default_price', '<=', $price);
    }

    public function sortByPrice($direction)
    {
        return $this->orderBy('default_price', strtolower($direction) === 'desc' ? 'desc' : 'asc');
    }
}
