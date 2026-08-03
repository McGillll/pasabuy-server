<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class ProductCategoryFilter extends ModelFilter
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

    public function leafOnly($query)
    {
        if (! filter_var($query, FILTER_VALIDATE_BOOLEAN)) {
            return $this;
        }

        return $this->whereDoesntHave('children');
    }
}
