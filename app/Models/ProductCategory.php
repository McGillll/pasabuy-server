<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['name', 'description', 'is_active'])]
class ProductCategory extends Model
{
    use Filterable, HasFactory, SoftDeletes;

    protected static function booted()
    {
        static::creating(function (ProductCategory $category) {
            $category->uuid = Str::uuid()->toString();
        });
    }

    protected function casts()
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function products()
    {
        return $this->hasMany(PasabuyProduct::class, 'category_id');
    }
}
