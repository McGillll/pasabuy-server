<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'logo_url', 'is_active'])]
class Brand extends Model
{
    use Filterable, HasFactory, SoftDeletes, UsesUuid;

    protected function casts()
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function products()
    {
        return $this->hasMany(PasabuyProduct::class, 'brand_id');
    }
}
