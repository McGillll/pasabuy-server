<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Basket extends Model
{
    use HasFactory, UsesUuid;

    protected $table = 'pasabuy_baskets';

    public function items()
    {
        return $this->hasMany(BasketItem::class, 'basket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
