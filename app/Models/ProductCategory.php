<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['name', 'description', 'is_active'])]
class ProductCategory extends Model
{
    use Filterable, HasFactory, SoftDeletes, UsesUuid;

    protected function casts()
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function setNameAttribute($value)
    {
        $titled = Str::title(trim($value));

        $this->attributes['name'] = preg_replace('/\bAnd\b/', '&', $titled);
    }

    public function products()
    {
        return $this->hasMany(PasabuyProduct::class, 'category_id');
    }
}
