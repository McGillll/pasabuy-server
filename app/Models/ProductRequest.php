<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['note', 'status'])]
class ProductRequest extends Model
{
    protected static function booted()
    {
        static::creating(function (ProductRequest $productRequest) {
            $productRequest->uuid = Str::uuid()->toString();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pasabuySchedule()
    {
        return $this->belongsTo(PasabuySchedule::class);
    }
}
