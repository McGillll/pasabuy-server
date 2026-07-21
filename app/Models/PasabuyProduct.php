<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'description', 'image_path', 'is_active', 'category_id', 'default_price', 'default_product_status'])]
class PasabuyProduct extends Model
{
    use Filterable, HasFactory, SoftDeletes, UsesUuid;

    protected function casts()
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function latestScheduleProduct()
    {
        return $this->hasOne(PasabuyScheduleProduct::class, 'product_id')->latestOfMany('updated_at');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
}
