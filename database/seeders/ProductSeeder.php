<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    protected const CATEGORIES = ['Noodles & Canned Goods', 'Beverages', 'Pantry Staples', 'Household'];

    protected const PRODUCTS = [
        ['name' => 'Instant Pancit Canton', 'description' => 'Spicy calamansi flavor, 60g pack', 'category' => 'Noodles & Canned Goods', 'default_price' => 18.00],
        ['name' => 'Corned Beef 210g', 'description' => 'Classic beef in can', 'category' => 'Noodles & Canned Goods', 'default_price' => 85.00],
        ['name' => 'Sardines in Tomato Sauce', 'description' => '155g can', 'category' => 'Noodles & Canned Goods', 'default_price' => 25.00],
        ['name' => 'Instant Coffee 3-in-1', 'description' => 'Box of 10 sachets', 'category' => 'Beverages', 'default_price' => 65.00],
        ['name' => 'Powdered Milk 1kg', 'description' => 'Full cream milk powder', 'category' => 'Beverages', 'default_price' => 320.00],
        ['name' => 'Soy Sauce 1L', 'description' => 'All-purpose soy sauce', 'category' => 'Pantry Staples', 'default_price' => 60.00],
        ['name' => 'Cooking Oil 1L', 'description' => 'Pure vegetable oil', 'category' => 'Pantry Staples', 'default_price' => 110.00],
        ['name' => 'White Rice 5kg', 'description' => 'Premium quality rice', 'category' => 'Pantry Staples', 'default_price' => 290.00],
        ['name' => 'Laundry Detergent Powder', 'description' => '1kg pack', 'category' => 'Household', 'default_price' => 75.00],
        ['name' => 'Bottled Water 500mL', 'description' => 'Purified drinking water', 'category' => 'Household', 'default_price' => 15.00],
    ];

    public function run()
    {
        if (Product::exists()) {
            return;
        }

        $categories = collect(self::CATEGORIES)->mapWithKeys(function ($name) {
            $category = new ProductCategory;
            $category->name = $name;
            $category->is_active = true;
            $category->save();

            return [$name => $category->id];
        });

        foreach (self::PRODUCTS as $item) {
            $product = new Product;
            $product->name = $item['name'];
            $product->description = $item['description'];
            $product->image_path = null;
            $product->is_active = true;
            $product->category_id = $categories[$item['category']];
            $product->default_price = $item['default_price'];
            $product->save();
        }
    }
}
