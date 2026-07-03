<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['name', 'description', 'image_path', 'is_active'])]
class Product extends Model
{
    use Filterable, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            $product->uuid = Str::uuid()->toString();
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
