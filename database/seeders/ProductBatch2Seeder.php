<?php

namespace Database\Seeders;

use App\Models\PasabuyProduct;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductBatch2Seeder extends Seeder
{
    protected const PRODUCTS = [
        ['name' => "Member's Value Coffee Creamer", 'description' => '1kg', 'image_path' => null, 'category' => 'Coffee | Milk | Creamer', 'default_price' => 176.00, 'default_product_status' => 'none'],
        ['name' => 'Zonrox ColorSafe Blossom Fresh', 'description' => '3600mL', 'image_path' => null, 'category' => 'Detergents', 'default_price' => 294.00, 'default_product_status' => 'none'],
        ['name' => 'Century Tuna Lite Flakes in Veg Oil', 'description' => '6x180g', 'image_path' => null, 'category' => 'Canned Goods', 'default_price' => 354.00, 'default_product_status' => 'none'],
        ['name' => 'Mega Sardines in Tomato Sauce', 'description' => '10x155g', 'image_path' => null, 'category' => 'Canned Goods', 'default_price' => 249.00, 'default_product_status' => 'none'],
        ['name' => 'Mega Sardines in Tomato Sauce Chili Added', 'description' => '10x155g', 'image_path' => null, 'category' => 'Canned Goods', 'default_price' => 249.50, 'default_product_status' => 'none'],
        ['name' => 'Mi Cocina Spanish Style Sardines in Olive Oil', 'description' => '2x230g', 'image_path' => null, 'category' => 'Canned Goods', 'default_price' => 372.00, 'default_product_status' => 'none'],
        ['name' => 'San Marino Corned Tuna', 'description' => '6x180g', 'image_path' => null, 'category' => 'Canned Goods', 'default_price' => 274.00, 'default_product_status' => 'none'],
        ['name' => 'Century Tuna Flakes in Oil', 'description' => '6x180g', 'image_path' => null, 'category' => 'Canned Goods', 'default_price' => 279.00, 'default_product_status' => 'none'],
        ['name' => 'Ranch House Chili Garlic Corned Beef', 'description' => '380g', 'image_path' => null, 'category' => 'Canned Goods', 'default_price' => 178.00, 'default_product_status' => 'none'],
        ['name' => 'Ranch House Corned Beef', 'description' => '380g', 'image_path' => null, 'category' => 'Canned Goods', 'default_price' => 172.00, 'default_product_status' => 'none'],
        ['name' => 'Delimondo Ranch Style Corned Beef', 'description' => '380g', 'image_path' => null, 'category' => 'Canned Goods', 'default_price' => 189.00, 'default_product_status' => 'none'],
        ['name' => "Motts 100% Apple Juice 6-Pack", 'description' => '6x237mL', 'image_path' => null, 'category' => 'Juices | Tea', 'default_price' => 359.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Roasted Unsalted Cashews", 'description' => '454g', 'image_path' => null, 'category' => 'Snacks | Junkfoods | Nuts', 'default_price' => 389.00, 'default_product_status' => 'none'],
        ['name' => 'Ocean Spray Cran x Apple Juice', 'description' => '1.89L', 'image_path' => null, 'category' => 'Juices | Tea', 'default_price' => 399.00, 'default_product_status' => 'b1t1'],
        ['name' => 'Ocean Spray Cran x Grape Juice', 'description' => '1.89L', 'image_path' => null, 'category' => 'Juices | Tea', 'default_price' => 399.00, 'default_product_status' => 'b1t1'],
        ['name' => 'Tropicana Twister Orange', 'description' => '1L', 'image_path' => null, 'category' => 'Juices | Tea', 'default_price' => 59.00, 'default_product_status' => 'none'],
        ['name' => 'SSK Sesame Dressing', 'description' => '1L', 'image_path' => null, 'category' => 'Sauce', 'default_price' => 499.00, 'default_product_status' => 'none'],
        ['name' => 'Hidden Valley Original Ranch Dressing', 'description' => '473mL', 'image_path' => null, 'category' => 'Sauce', 'default_price' => 375.00, 'default_product_status' => 'none'],
        ['name' => 'Wish-Bone Thousand Island Dressing', 'description' => '444mL', 'image_path' => null, 'category' => 'Sauce', 'default_price' => 249.00, 'default_product_status' => 'none'],
        ['name' => 'Huy Fong Sriracha Hot Chili Sauce', 'description' => '793g', 'image_path' => null, 'category' => 'Sauce', 'default_price' => 449.00, 'default_product_status' => 'none'],
        ['name' => 'Samyang Buldak Hot Sauce Original', 'description' => '200g', 'image_path' => null, 'category' => 'Sauce', 'default_price' => 169.00, 'default_product_status' => 'none'],
        ['name' => 'Crying Thaiger Sriracha Hot Chilli Sauce', 'description' => '740mL', 'image_path' => null, 'category' => 'Sauce', 'default_price' => 259.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Tomato Ketchup", 'description' => '907g', 'image_path' => null, 'category' => 'Sauce', 'default_price' => 189.00, 'default_product_status' => 'none'],
        ['name' => 'Nutella B-ready', 'description' => '6x22g (132g)', 'image_path' => null, 'category' => 'Crackers | Biscuits', 'default_price' => 194.00, 'default_product_status' => 'none'],
        ['name' => 'Nestle Koko Krunch Family Pack', 'description' => '900g (2x450g)', 'image_path' => null, 'category' => 'Cereals', 'default_price' => 475.00, 'default_product_status' => 'none'],
        ["name" => "Kellogg's Corn Flakes", 'description' => '2 bags, 43oz total (1219g)', 'image_path' => null, 'category' => 'Cereals', 'default_price' => 674.00, 'default_product_status' => 'none'],
        ['name' => 'IKO Cheddar Cheese Cream Sandwich', 'description' => '180g', 'image_path' => null, 'category' => 'Crackers | Biscuits', 'default_price' => 179.00, 'default_product_status' => 'b1t1'],
        ['name' => 'Massimo Ciambella Strawberry', 'description' => '6x300g', 'image_path' => null, 'category' => 'Crackers | Biscuits', 'default_price' => 289.00, 'default_product_status' => 'b1t1'],
        ['name' => 'Samyang Buldak Hot Sauce Carbonara', 'description' => '200g', 'image_path' => null, 'category' => 'Sauce', 'default_price' => 189.00, 'default_product_status' => 'none'],
        ['name' => 'Ottogi Jin Ramen Mild', 'description' => '5x120g', 'image_path' => null, 'category' => 'Noodles', 'default_price' => 209.00, 'default_product_status' => 'none'],
        ['name' => 'Indomie Mi Goreng', 'description' => '10x85g', 'image_path' => null, 'category' => 'Noodles', 'default_price' => 165.00, 'default_product_status' => 'none'],
        ['name' => 'Samyang Original Ramen', 'description' => '5x120g', 'image_path' => null, 'category' => 'Noodles', 'default_price' => 229.00, 'default_product_status' => 'none'],
        ['name' => 'Indomie Mi Goreng Hot and Spicy', 'description' => '10x80g', 'image_path' => null, 'category' => 'Noodles', 'default_price' => 165.00, 'default_product_status' => 'none'],
        ['name' => 'Samyang MEP Gochujang Basil', 'description' => '4x80g', 'image_path' => null, 'category' => 'Noodles', 'default_price' => 139.00, 'default_product_status' => 'none'],
        ['name' => 'Samyang MEP Korean Chilli Crab', 'description' => '4x80g', 'image_path' => null, 'category' => 'Noodles', 'default_price' => 139.00, 'default_product_status' => 'none'],
        ['name' => 'Yopokki Cheese Rapokki Pouch', 'description' => '260g', 'image_path' => null, 'category' => 'Noodles', 'default_price' => 199.00, 'default_product_status' => 'none'],
        ['name' => 'Gold Kili Double Shot Instant White Coffee', 'description' => '15x35g', 'image_path' => null, 'category' => 'Coffee | Milk | Creamer', 'default_price' => 299.00, 'default_product_status' => 'none'],
        ['name' => 'Gold Kili Less Sugar 3-in-1 Coffee Mix', 'description' => '25x18g', 'image_path' => null, 'category' => 'Coffee | Milk | Creamer', 'default_price' => 224.00, 'default_product_status' => 'none'],
        ["name" => "Nature's Truth Multi Collagen Peptides", 'description' => '90ct', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 699.00, 'default_product_status' => 'none'],
        ["name" => "Nature's Truth ABC Complete Multivitamins Women's", 'description' => '100ct', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 649.00, 'default_product_status' => 'none'],
        ['name' => 'Webber Naturals Zinc Citrate 50mg', 'description' => '180ct', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 259.00, 'default_product_status' => 'none'],
        ['name' => 'Webber Naturals Vitamin B-Complex', 'description' => '120ct', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 479.00, 'default_product_status' => 'none'],
        ['name' => 'Centrum Silver Multivitamins Adults 50+', 'description' => '325ct', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 1999.00, 'default_product_status' => 'none'],
        ['name' => 'Webber Naturals Calcium w/Vit D3', 'description' => '300ct', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 999.00, 'default_product_status' => 'none'],
        ['name' => 'Head & Shoulders Smooth & Silky Shampoo', 'description' => null, 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 614.00, 'default_product_status' => 'none'],
        ['name' => 'Dove Anti-Frizz Shampoo', 'description' => '532mL', 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 449.00, 'default_product_status' => 'none'],
        ['name' => 'Dove Shampoo Daily Moisture', 'description' => '828mL', 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 519.00, 'default_product_status' => 'none'],
        ['name' => 'TRESemme Shampoo Keratin Smooth Blowout', 'description' => '828mL', 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 429.00, 'default_product_status' => 'none'],
        ['name' => 'TRESemme Shampoo Keratin Repair', 'description' => '828mL', 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 429.00, 'default_product_status' => 'none'],
        ['name' => 'TRESemme Conditioner Keratin Repair', 'description' => '828mL', 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 429.00, 'default_product_status' => 'none'],
        ['name' => 'TRESemme Conditioner Keratin Smooth', 'description' => '828mL', 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 429.00, 'default_product_status' => 'none'],
        ['name' => 'Dove Anti-Frizz Oil Conditioner', 'description' => '603mL', 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 449.00, 'default_product_status' => 'none'],
        ['name' => 'CreamSilk Conditioner Triple Keratin Straight', 'description' => null, 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 737.00, 'default_product_status' => 'none'],
        ['name' => 'Webber Naturals Omega 3-6-9 High Potency', 'description' => '1200mg, 280 Softgels', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 1229.00, 'default_product_status' => 'none'],
        ['name' => 'Nature Made Fish Oil', 'description' => '1200mg, 300ct', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 1899.00, 'default_product_status' => 'none'],
        ['name' => 'Kirkland Signature Fish Oil Omega-3', 'description' => '1000mg, 400ct', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 1399.00, 'default_product_status' => 'none'],
        ['name' => 'Webber Naturals Omega-3 Wild Fish Oil', 'description' => '1000mg, 300 Softgels', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 1699.00, 'default_product_status' => 'none'],
        ['name' => 'Webber Naturals Collagen Plus with L-lysine & Vitamin C', 'description' => '500mg, 300ct', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 1549.00, 'default_product_status' => 'none'],
        ['name' => 'Webber Naturals Vitamin E Clear Base', 'description' => '400IU, 300ct', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 2499.00, 'default_product_status' => 'none'],
        ['name' => 'NeoCell Super Collagen Peptides Powder', 'description' => '10g, 600g', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 2129.00, 'default_product_status' => 'none'],
        ['name' => 'Webber Naturals Bioactive Collagen Peptides', 'description' => '180 Tablets', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 969.00, 'default_product_status' => 'none'],
        ['name' => 'Webber Naturals Glutathione', 'description' => '250mg, 120 Capsules', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 1499.00, 'default_product_status' => 'none'],
        ['name' => 'Kirkland Signature Non-Drowsy AllerClear', 'description' => 'Loratadine 10mg, 365 Tablets', 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 949.00, 'default_product_status' => 'none'],
        ['name' => 'Tiger Balm Red Ointment', 'description' => '2x30g', 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 229.00, 'default_product_status' => 'none'],
        ['name' => 'Tiger Balm Warm Plaster', 'description' => 'Small, 3 packs (6 plasters)', 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 219.00, 'default_product_status' => 'none'],
        ['name' => 'Kool Fever Kids Cooling Patch', 'description' => '6ct', 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 149.00, 'default_product_status' => 'none'],
        ['name' => 'Webber Naturals Melatonin Gummy', 'description' => '10mg, 60ct', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 479.00, 'default_product_status' => 'none'],
        ["name" => "Nature's Truth Just 4 Kidz Probiotic-14 Chewable", 'description' => '3 Billion CFU, 30 Chewable Tablets', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 369.00, 'default_product_status' => 'none'],
        ['name' => 'Webber Naturals Evening Primrose Oil', 'description' => '500mg, 360ct', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 1499.00, 'default_product_status' => 'none'],
        ['name' => 'Bragg Apple Cider Vinegar', 'description' => '1877mg, 180ct', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 2299.00, 'default_product_status' => 'none'],
        ['name' => 'Webber Naturals Apple Cider Vinegar', 'description' => '500mg, 240 Capsules', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 599.00, 'default_product_status' => 'none'],
        ['name' => 'Genacol Original Collagen', 'description' => '400mg, 90 Capsules', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 1799.00, 'default_product_status' => 'none'],
        ['name' => 'Caltrate Bone Health Advanced', 'description' => '600mg+D3, 320ct', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 1799.00, 'default_product_status' => 'none'],
        ["name" => "Nature's Truth Iron Ferrous Sulfate", 'description' => '65mg, 325mg, 240ct', 'image_path' => null, 'category' => 'Vitamins | Supplements', 'default_price' => 649.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Cinnamon Stick", 'description' => '100g', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 129.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Ground Cinnamon", 'description' => '200g', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 139.00, 'default_product_status' => 'none'],
        ['name' => 'Miss VietSpice Whole Cloves', 'description' => '190g', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 249.00, 'default_product_status' => 'none'],
        ['name' => 'Miss VietSpice Ground Ginger', 'description' => '200g', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 129.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Ground Paprika", 'description' => '200g', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 139.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Thyme Flakes", 'description' => '70g', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 99.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Star Anise", 'description' => '100g', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 159.00, 'default_product_status' => 'none'],
        ['name' => 'Miss VietSpice Cilantro Leaves', 'description' => '55g', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 79.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Parsley Flakes", 'description' => '50g', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 89.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Oregano Flakes", 'description' => '80g', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 99.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Crushed Red Pepper", 'description' => '200g', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 149.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Coarse Grain Sea Salt", 'description' => '1.1kg (coarse and refill)', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 175.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Himalayan Pink Salt", 'description' => '1.06kg, coarse grain', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 229.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Fine Grain Sea Salt", 'description' => '600g', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 79.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Garlic Salt", 'description' => '450g', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 149.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Lemon Pepper Seasoning", 'description' => '370g', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 235.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Ground White Pepper", 'description' => '250g', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 379.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Ground Black Pepper", 'description' => '250g', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 299.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Rosemary Flakes", 'description' => '80g', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 109.00, 'default_product_status' => 'none'],
        ["name" => "Member's Value Ground Onion", 'description' => '270g', 'image_path' => null, 'category' => 'Spices | Seasoning', 'default_price' => 189.00, 'default_product_status' => 'none'],
    ];

    public function run()
    {
        DB::transaction(function () {
            $categories = ProductCategory::pluck('id', 'name');

            foreach (self::PRODUCTS as $item) {
                if (PasabuyProduct::where('name', $item['name'])->exists()) {
                    continue;
                }

                $product = new PasabuyProduct;
                $product->name = $item['name'];
                $product->description = $item['description'];
                $product->image_path = $item['image_path'];
                $product->is_active = true;
                $product->category_id = $item['category'] ? $categories[$item['category']] : null;
                $product->default_price = $item['default_price'];
                $product->default_product_status = $item['default_product_status'];
                $product->save();
            }
        });
    }
}
