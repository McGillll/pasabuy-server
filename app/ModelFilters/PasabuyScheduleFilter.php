<?php 

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class PasabuyScheduleFilter extends ModelFilter
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
        return $this->where('title', 'like', "%{$query}%");
    }

    public function month($month)
    {
        return $this->whereMonth('pasabuy_schedule_date', $month);
    }

    public function year($year)
    {
        return $this->whereYear('pasabuy_schedule_date', $year);
    }
}
