<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    protected const CATEGORIES = [
        'Cooking Oil',
        'Spread | Palaman',
        'Detergents',
        'Dishwashing',
        'Coffee | Milk | Creamer',
        'Sauce',
        'Noodles',
        'Juices | Tea',
        'Crackers | Biscuits',
        'Personal & Body Care',
        'Snacks | Junkfoods | Nuts',
        'Sweets',
        'Bakery',
        'Meat',
        'Household Items',
        'Dairy',
        'Cereals',
        'Appliances',
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
