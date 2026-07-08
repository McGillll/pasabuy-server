<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['name', 'description', 'image_path', 'is_active', 'category_id', 'default_price', 'default_product_status'])]
class Product extends Model
{
    use Filterable, HasFactory, SoftDeletes;

    protected static function booted()
    {
        static::creating(function (Product $product) {
            $product->uuid = Str::uuid()->toString();
        });
    }

    protected function casts()
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function latestScheduleProduct()
    {
        return $this->hasOne(PasabuyScheduleProduct::class)->latestOfMany('updated_at');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
}
