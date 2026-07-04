<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    protected const PRODUCTS = [
        ['name' => 'Instant Pancit Canton', 'description' => 'Spicy calamansi flavor, 60g pack'],
        ['name' => 'Corned Beef 210g', 'description' => 'Classic beef in can'],
        ['name' => 'Sardines in Tomato Sauce', 'description' => '155g can'],
        ['name' => 'Instant Coffee 3-in-1', 'description' => 'Box of 10 sachets'],
        ['name' => 'Powdered Milk 1kg', 'description' => 'Full cream milk powder'],
        ['name' => 'Soy Sauce 1L', 'description' => 'All-purpose soy sauce'],
        ['name' => 'Cooking Oil 1L', 'description' => 'Pure vegetable oil'],
        ['name' => 'White Rice 5kg', 'description' => 'Premium quality rice'],
        ['name' => 'Laundry Detergent Powder', 'description' => '1kg pack'],
        ['name' => 'Bottled Water 500mL', 'description' => 'Purified drinking water'],
    ];

    public function run()
    {
        if (Product::exists()) {
            return;
        }

        foreach (self::PRODUCTS as $item) {
            $product = new Product;
            $product->name = $item['name'];
            $product->description = $item['description'];
            $product->image_path = null;
            $product->is_active = true;
            $product->save();
        }
    }
}
