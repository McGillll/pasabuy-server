<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['quantity'])]
class BasketItem extends Model
{
    use UsesUuid;

    protected $table = 'pasabuy_basket_items';

    public function basket()
    {
        return $this->belongsTo(Basket::class);
    }

    public function product()
    {
        return $this->belongsTo(PasabuyProduct::class, 'product_id');
    }
}
