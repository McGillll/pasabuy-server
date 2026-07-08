<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    protected const CATEGORIES = ['Noodles & Canned Goods', 'Beverages', 'Pantry Staples', 'Household'];

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
