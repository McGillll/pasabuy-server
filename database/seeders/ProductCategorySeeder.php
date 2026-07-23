<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    protected const CATEGORIES = [
        'Detergents',
        'Sweets',
        'Spread',
        'Bath Soap',
        'Shampoo',
        'Conditioner',
        'Cooking Oil',
        'Seasonings',
        'Snacks | Junkfoods | Nuts',
        'Appliances',
        'Cangoods',
        'Coffee | Milk | Creamer',
        'Dishwashing',
        'Sauce',
        'Noodles',
        'Juices | Tea',
        'Biscuits',
        'Buy1 Take1',
        'Personal & Body Care',
    ];

    public function run()
    {
        if (ProductCategory::exists()) {
            return;
        }

        foreach (self::CATEGORIES as $name) {
            $category = new ProductCategory;
            $category->name = $name;
            $category->is_active = true;
            $category->save();
        }
    }
}
