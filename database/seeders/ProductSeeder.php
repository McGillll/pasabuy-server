<?php

namespace Database\Seeders;

use App\Models\PasabuyProduct;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    protected const PRODUCTS = [
        ['name' => 'Patrick', 'description' => null, 'image_path' => 'products/patrick-6a577d79d8dfe.webp', 'category' => 'Crackers | Biscuits', 'default_price' => 259.00, 'default_product_status' => 'b1t1'],
        ['name' => 'Veg oil', 'description' => null, 'image_path' => 'products/veg-oil-6a577d868c036.webp', 'category' => 'Cooking Oil', 'default_price' => 599.00, 'default_product_status' => 'none'],
        ['name' => 'Biscoff', 'description' => null, 'image_path' => 'products/biscoff-6a577d5f269fc.webp', 'category' => 'Spread | Palaman', 'default_price' => 509.00, 'default_product_status' => 'none'],
        ['name' => 'MV Detergent bucket', 'description' => null, 'image_path' => 'products/mv-detergent-bucket-6a577dcd478c1.webp', 'category' => 'Detergents', 'default_price' => 899.00, 'default_product_status' => 'none'],
        ['name' => 'Mugs Set in Tower', 'description' => null, 'image_path' => 'products/mugs-set-in-tower-6a59f65181f92.webp', 'category' => 'Household Items', 'default_price' => 500.00, 'default_product_status' => 'none'],
        ['name' => 'Turbo 9inch fan', 'description' => null, 'image_path' => 'products/turbo-9inch-fan-6a59fe1ff12e4.webp', 'category' => 'Appliances', 'default_price' => 1395.00, 'default_product_status' => 'b1t1'],
        ['name' => 'MV all purpose dressing', 'description' => null, 'image_path' => 'products/mv-all-purpose-dressing-6a5a01e88e5a5.webp', 'category' => 'Sauce', 'default_price' => 175.00, 'default_product_status' => 'none'],
        ['name' => 'MV real Mayo', 'description' => null, 'image_path' => 'products/mv-real-mayo-6a5a02099c4f3.webp', 'category' => 'Spread | Palaman', 'default_price' => 279.00, 'default_product_status' => 'none'],
        ['name' => 'MV Fabcon', 'description' => null, 'image_path' => 'products/mv-fabcon-6a5a02452503c.webp', 'category' => 'Detergents', 'default_price' => 379.00, 'default_product_status' => 'none'],
        ['name' => 'MV Liquid detergent', 'description' => null, 'image_path' => 'products/mv-liquid-detergent-6a5a0286aed02.webp', 'category' => 'Detergents', 'default_price' => 249.00, 'default_product_status' => 'none'],
        ['name' => "Taster's Choice instant Decaf coffee", 'description' => null, 'image_path' => 'products/tasters-choice-instant-decaf-coffee-6a5ef6fe04db4.webp', 'category' => 'Coffee | Milk | Creamer', 'default_price' => 1729.00, 'default_product_status' => 'none'],
        ['name' => 'Nescafe Gold DECAF', 'description' => null, 'image_path' => 'products/nescafe-gold-decaf-6a5ef89a5551e.webp', 'category' => 'Coffee | Milk | Creamer', 'default_price' => 699.00, 'default_product_status' => 'none'],
        ['name' => 'Zip LEMON', 'description' => null, 'image_path' => 'products/zip-lemon-6a5ef8cd971b7.webp', 'category' => 'Dishwashing', 'default_price' => 399.00, 'default_product_status' => 'none'],
        ['name' => 'Labour', 'description' => null, 'image_path' => 'products/labour-6a5ef9266c689.webp', 'category' => 'Dishwashing', 'default_price' => 199.00, 'default_product_status' => 'none'],
        ['name' => 'Mang Tomas', 'description' => null, 'image_path' => 'products/mang-tomas-6a5efa375ad27.webp', 'category' => 'Sauce', 'default_price' => 51.00, 'default_product_status' => 'none'],
        ['name' => 'Suree Oyster Sauce', 'description' => null, 'image_path' => 'products/suree-oyster-sauce-6a5efaa63b863.webp', 'category' => 'Sauce', 'default_price' => 159.00, 'default_product_status' => 'none'],
        ['name' => 'Suree Sweet Plum', 'description' => null, 'image_path' => 'products/suree-sweet-plum-6a5f478ab1ead.webp', 'category' => 'Sauce', 'default_price' => 189.00, 'default_product_status' => 'none'],
        ['name' => 'Suree Sweet SoySauce', 'description' => null, 'image_path' => 'products/suree-sweet-soysauce-6a5f47e36001e.webp', 'category' => 'Sauce', 'default_price' => 239.00, 'default_product_status' => 'none'],
        ['name' => 'Paldo Volcano Carbonara', 'description' => null, 'image_path' => 'products/paldo-volcano-carbonara-6a5f50e758776.webp', 'category' => 'Noodles', 'default_price' => 244.00, 'default_product_status' => 'none'],
        ['name' => 'Nissin Cup Noodles', 'description' => null, 'image_path' => 'products/nissin-cup-noodles-6a5f5195cf6e2.webp', 'category' => 'Noodles', 'default_price' => 119.00, 'default_product_status' => 'none'],
        ['name' => 'Lipton Lemon Tea', 'description' => '2.54 kg', 'image_path' => 'products/lipton-lemon-tea-6a5f52e7916aa.webp', 'category' => 'Juices | Tea', 'default_price' => 759.00, 'default_product_status' => 'none'],
        ['name' => 'BG Honey Citron&Ginger tea', 'description' => null, 'image_path' => 'products/bg-honey-citronginger-tea-6a5f5712173be.webp', 'category' => 'Juices | Tea', 'default_price' => 369.00, 'default_product_status' => 'none'],
        ['name' => 'Milk Classic rice', 'description' => null, 'image_path' => 'products/milk-classic-rice-6a5f57e6dc479.webp', 'category' => 'Crackers | Biscuits', 'default_price' => 159.00, 'default_product_status' => 'none'],
        ['name' => 'Delico Wafer', 'description' => null, 'image_path' => 'products/delico-wafer-6a5f5833e69a9.webp', 'category' => 'Crackers | Biscuits', 'default_price' => 199.00, 'default_product_status' => 'b1t1'],
        ['name' => 'Motts 100% Apple juice', 'description' => '2.5 L', 'image_path' => 'products/motts-100-apple-juice-6a5f588ddd1d9.webp', 'category' => 'Juices | Tea', 'default_price' => 339.00, 'default_product_status' => 'none'],
        ['name' => 'Lotte Mandarin Juice', 'description' => '1.5L', 'image_path' => 'products/lotte-mandarin-juice-6a5f58f5dbb8e.webp', 'category' => 'Juices | Tea', 'default_price' => 129.00, 'default_product_status' => 'none'],
        ['name' => 'Corn pops', 'description' => '520g', 'image_path' => 'products/corn-pops-6a5f595d2e840.webp', 'category' => 'Snacks | Junkfoods | Nuts', 'default_price' => 159.00, 'default_product_status' => 'none'],
        ['name' => 'Corn balls', 'description' => '420g', 'image_path' => 'products/corn-balls-6a5f59c9291f8.webp', 'category' => 'Snacks | Junkfoods | Nuts', 'default_price' => 219.00, 'default_product_status' => 'none'],
        ['name' => 'Gourmet veg crackers', 'description' => null, 'image_path' => 'products/gourmet-veg-crackers-6a60674fd7cf0.webp', 'category' => 'Crackers | Biscuits', 'default_price' => 159.00, 'default_product_status' => 'none'],
        ['name' => 'Krit b1t1', 'description' => null, 'image_path' => 'products/krit-b1t1-6a60679821eeb.webp', 'category' => 'Crackers | Biscuits', 'default_price' => 189.00, 'default_product_status' => 'b1t1'],
        ['name' => 'SKIPPY', 'description' => '1.3 kg', 'image_path' => 'products/skippy-6a6068a250e4e.webp', 'category' => 'Spread | Palaman', 'default_price' => 498.00, 'default_product_status' => 'none'],
        ['name' => 'MV Hazelnut', 'description' => null, 'image_path' => 'products/mv-hazelnut-6a61a7038e7fa.webp', 'category' => 'Spread | Palaman', 'default_price' => 399.00, 'default_product_status' => 'none'],
        ['name' => 'Nutella', 'description' => null, 'image_path' => 'products/nutella-6a61a76511ff9.webp', 'category' => 'Spread | Palaman', 'default_price' => 599.00, 'default_product_status' => 'none'],
        ['name' => 'Arla Cheesy spread', 'description' => null, 'image_path' => 'products/arla-cheesy-spread-6a61a790b9228.webp', 'category' => 'Spread | Palaman', 'default_price' => 238.00, 'default_product_status' => 'none'],
        ['name' => 'Arla Cheesy Cheddar', 'description' => null, 'image_path' => 'products/arla-cheesy-cheddar-6a61a7b789c0b.webp', 'category' => 'Spread | Palaman', 'default_price' => 260.00, 'default_product_status' => 'none'],
        ['name' => 'Pinar Cream Cheese Cheddar', 'description' => null, 'image_path' => 'products/pinar-cream-cheese-cheddar-6a61a80cdb5ae.webp', 'category' => 'Spread | Palaman', 'default_price' => 219.00, 'default_product_status' => 'none'],
        ['name' => 'Peanut Butter Creamy', 'description' => null, 'image_path' => 'products/peanut-butter-creamy-6a61a886038f9.webp', 'category' => 'Spread | Palaman', 'default_price' => 199.00, 'default_product_status' => 'none'],
        ['name' => 'Peanut butter Crunchy', 'description' => null, 'image_path' => 'products/peanut-butter-crunchy-6a61a91225a89.webp', 'category' => 'Spread | Palaman', 'default_price' => 199.00, 'default_product_status' => 'none'],
        ['name' => 'Colgate', 'description' => null, 'image_path' => 'products/colgate-6a61a9890b122.webp', 'category' => 'Personal & Body Care', 'default_price' => 349.00, 'default_product_status' => 'none'],
        ['name' => 'Hand soap Aloevera', 'description' => null, 'image_path' => 'products/hand-soap-aloevera-6a61aa4acb18c.webp', 'category' => 'Personal & Body Care', 'default_price' => 69.00, 'default_product_status' => 'none'],
        ['name' => 'Hand soap Lavender', 'description' => null, 'image_path' => 'products/hand-soap-lavender-6a61aa868c7db.webp', 'category' => 'Personal & Body Care', 'default_price' => 69.00, 'default_product_status' => 'none'],
        ['name' => 'hand soap Sheabutter', 'description' => null, 'image_path' => 'products/hand-soap-sheabutter-6a61aaab15380.webp', 'category' => 'Personal & Body Care', 'default_price' => 69.00, 'default_product_status' => 'none'],
        ['name' => 'Super garlic', 'description' => null, 'image_path' => 'products/super-garlic-6a61ab1261ab1.webp', 'category' => 'Snacks | Junkfoods | Nuts', 'default_price' => 189.00, 'default_product_status' => 'none'],
        ['name' => 'Calbee Honey Butter', 'description' => null, 'image_path' => 'products/calbee-honey-butter-6a61ad776c267.webp', 'category' => 'Snacks | Junkfoods | Nuts', 'default_price' => 249.00, 'default_product_status' => 'none'],
        ['name' => 'Lotte kokkal Grilled Corn Snacks', 'description' => null, 'image_path' => 'products/lotte-kokkal-grilled-corn-snacks-6a61adc6424ed.webp', 'category' => 'Snacks | Junkfoods | Nuts', 'default_price' => 239.00, 'default_product_status' => 'none'],
        ['name' => 'Fried Peanut', 'description' => null, 'image_path' => 'products/fried-peanut-6a61adf9996fa.webp', 'category' => 'Snacks | Junkfoods | Nuts', 'default_price' => 239.00, 'default_product_status' => 'none'],
        ['name' => 'Herman Peanut butter (red)', 'description' => null, 'image_path' => 'products/herman-peanut-butter-red-6a61ae33bb239.webp', 'category' => 'Spread | Palaman', 'default_price' => 199.00, 'default_product_status' => 'none'],
        ['name' => 'Smartfood', 'description' => null, 'image_path' => 'products/smartfood-6a61ae5221d53.webp', 'category' => 'Snacks | Junkfoods | Nuts', 'default_price' => 101.00, 'default_product_status' => 'none'],
        ['name' => 'MV dishwashing', 'description' => null, 'image_path' => null, 'category' => 'Dishwashing', 'default_price' => 299.00, 'default_product_status' => 'none'],
        ['name' => 'OREO', 'description' => null, 'image_path' => 'products/oreo-6a61aeaa436a6.webp', 'category' => 'Crackers | Biscuits', 'default_price' => 146.00, 'default_product_status' => 'b1t1'],
        ['name' => 'Maxon', 'description' => null, 'image_path' => 'products/maxon-6a61aed2f1fea.webp', 'category' => 'Crackers | Biscuits', 'default_price' => 159.00, 'default_product_status' => 'b1t1'],
        ['name' => 'Cetaphil Cleanser', 'description' => null, 'image_path' => 'products/cetaphil-cleanser-6a61b08375207.webp', 'category' => 'Personal & Body Care', 'default_price' => 849.00, 'default_product_status' => 'none'],
        ['name' => 'Cetaphil Lotion', 'description' => null, 'image_path' => 'products/cetaphil-lotion-6a61b0c0e8fd1.webp', 'category' => 'Personal & Body Care', 'default_price' => 849.00, 'default_product_status' => 'none'],
        ['name' => 'Folgers Instant Classic Roast Coffee', 'description' => '16 oz', 'image_path' => null, 'category' => 'Coffee | Milk | Creamer', 'default_price' => 849.00, 'default_product_status' => 'none'],
        ['name' => 'Member\'s Value 100% Colombian Instant Decaf', 'description' => null, 'image_path' => null, 'category' => 'Coffee | Milk | Creamer', 'default_price' => 599.00, 'default_product_status' => 'none'],
        ['name' => 'Nescafe Gold', 'description' => '190g', 'image_path' => null, 'category' => 'Coffee | Milk | Creamer', 'default_price' => 564.00, 'default_product_status' => 'none'],
        ['name' => 'Nescafe Tradicao Forte', 'description' => '200g', 'image_path' => null, 'category' => 'Coffee | Milk | Creamer', 'default_price' => 569.00, 'default_product_status' => 'b1t1'],
        ['name' => 'Blakk Tower Fan 33inch', 'description' => null, 'image_path' => null, 'category' => 'Appliances', 'default_price' => 2395.00, 'default_product_status' => 'b1t1'],
        ['name' => 'Orion Choco Pie', 'description' => '30x39g', 'image_path' => null, 'category' => 'Crackers | Biscuits', 'default_price' => 349.00, 'default_product_status' => 'none'],
        ['name' => 'S&R Artisan Bread Sourdough Loaf', 'description' => '450g', 'image_path' => null, 'category' => 'Bakery', 'default_price' => 119.00, 'default_product_status' => 'none'],
        ['name' => 'Tostitos Creamy Spinach Dip', 'description' => '425.2g', 'image_path' => null, 'category' => 'Snacks | Junkfoods | Nuts', 'default_price' => 216.00, 'default_product_status' => 'none'],
        ['name' => 'Bergen Cookies Obsession Cream', 'description' => 'Chocolate Biscuit & Vanilla Filling, 96g', 'image_path' => null, 'category' => 'Crackers | Biscuits', 'default_price' => 129.00, 'default_product_status' => 'b1t1'],
        ['name' => 'Bergen Cookies Obsession Peanut Butter', 'description' => 'Cocoa Biscuit & Milk Chocolate, 96g', 'image_path' => null, 'category' => 'Crackers | Biscuits', 'default_price' => 129.00, 'default_product_status' => 'b1t1'],
        ['name' => 'Elgorriaga Break Cocoa Biscuit with Milk Cream Filling', 'description' => '500g', 'image_path' => null, 'category' => 'Crackers | Biscuits', 'default_price' => 299.00, 'default_product_status' => 'b1t1'],
        ['name' => 'Massimo Ciambella Cacao', 'description' => '6x300g', 'image_path' => null, 'category' => 'Crackers | Biscuits', 'default_price' => 289.00, 'default_product_status' => 'b1t1'],
        ['name' => 'Cuetara Krit Pub-Mix Crackers', 'description' => '350g', 'image_path' => null, 'category' => 'Crackers | Biscuits', 'default_price' => 189.00, 'default_product_status' => 'b1t1'],
        ['name' => 'MV Beef Ribeye Steak', 'description' => 'per kg', 'image_path' => null, 'category' => 'Meat', 'default_price' => 919.00, 'default_product_status' => 'none'],
        ['name' => 'Beef Chuck Steak', 'description' => 'per kg', 'image_path' => null, 'category' => 'Meat', 'default_price' => 653.00, 'default_product_status' => 'none'],
        ['name' => 'Beef Top Blade Steak', 'description' => 'per kg', 'image_path' => null, 'category' => 'Meat', 'default_price' => 665.00, 'default_product_status' => 'none'],
        ['name' => 'MV Beef Minute Steak', 'description' => 'per kg', 'image_path' => null, 'category' => 'Meat', 'default_price' => 569.00, 'default_product_status' => 'none'],
        ['name' => 'PCT OPP Blanket Blue Jasper', 'description' => 'Queen', 'image_path' => null, 'category' => 'Household Items', 'default_price' => 1299.00, 'default_product_status' => 'b1t1'],
        ['name' => 'Blakk Electric Stand Fan 16inch', 'description' => null, 'image_path' => null, 'category' => 'Appliances', 'default_price' => 2395.00, 'default_product_status' => 'b1t1'],
        ['name' => 'Kuchenluxe Infrared Cooker', 'description' => null, 'image_path' => null, 'category' => 'Appliances', 'default_price' => 2295.00, 'default_product_status' => 'none'],
        ['name' => 'ABC Jelly Fruity Cup Fruity Yogu Assorted', 'description' => '1400g', 'image_path' => null, 'category' => 'Sweets', 'default_price' => 359.00, 'default_product_status' => 'none'],
        ['name' => 'S&R Ube Cheese Loaf', 'description' => null, 'image_path' => null, 'category' => 'Bakery', 'default_price' => 229.00, 'default_product_status' => 'b1t1'],
        ['name' => 'S&R Mocha Marble Loaf', 'description' => null, 'image_path' => null, 'category' => 'Bakery', 'default_price' => 229.00, 'default_product_status' => 'b1t1'],
        ['name' => 'B1T1 Variety 2 Muffin', 'description' => '6ct, Cheese/Choco Almond', 'image_path' => null, 'category' => 'Bakery', 'default_price' => 379.00, 'default_product_status' => 'b1t1'],
        ['name' => 'Cuetara Krititas Bote Crackers', 'description' => '350g', 'image_path' => null, 'category' => 'Crackers | Biscuits', 'default_price' => 109.00, 'default_product_status' => 'none'],
        ['name' => 'Daisy Margarine', 'description' => '480g', 'image_path' => null, 'category' => 'Spread | Palaman', 'default_price' => 119.00, 'default_product_status' => 'none'],
        ['name' => 'Herman Crunchy Biscuit Spread', 'description' => '560g', 'image_path' => null, 'category' => 'Spread | Palaman', 'default_price' => 249.00, 'default_product_status' => 'none'],
        ['name' => 'Cheez Whiz Pimiento', 'description' => '440g', 'image_path' => null, 'category' => 'Spread | Palaman', 'default_price' => 197.00, 'default_product_status' => 'none'],
        ['name' => 'Lifetime 6FT Fold-in-Half Table Almond', 'description' => null, 'image_path' => null, 'category' => 'Household Items', 'default_price' => 4199.00, 'default_product_status' => 'b1t1'],
        ['name' => 'Lifetime 4FT Fold-in-Half Table Almond', 'description' => null, 'image_path' => null, 'category' => 'Household Items', 'default_price' => 1699.00, 'default_product_status' => 'none'],
        ['name' => 'Safeguard Bodywash Floral Pink', 'description' => '1L', 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 249.00, 'default_product_status' => 'none'],
        ['name' => 'Palmolive Shampoo Intensive Moisture Coconut Cream', 'description' => '600ml', 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 279.00, 'default_product_status' => 'none'],
        ['name' => 'Palmolive Shampoo Ultra Smooth Aloe Vera', 'description' => '600ml', 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 279.00, 'default_product_status' => 'none'],
        ['name' => 'Pantene Miracles Conditioner Biotin Hair Fall Control', 'description' => '400ml', 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 399.00, 'default_product_status' => 'none'],
        ['name' => 'Pantene Miracles Conditioner Collagen Damage Repair', 'description' => '480ml', 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 419.00, 'default_product_status' => 'none'],
        ['name' => "Member's Value 70% Ethyl Alcohol", 'description' => '1L', 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 159.00, 'default_product_status' => 'none'],
        ['name' => 'Softlete Cleansing Wipes', 'description' => '100s', 'image_path' => null, 'category' => 'Personal & Body Care', 'default_price' => 99.00, 'default_product_status' => 'none'],
        ['name' => 'Sungrains Dried Fruits & Nuts Mixed', 'description' => '500g', 'image_path' => null, 'category' => 'Snacks | Junkfoods | Nuts', 'default_price' => 199.00, 'default_product_status' => 'none'],
        ['name' => 'Lotte Milkis Regular Can', 'description' => '6x250ml', 'image_path' => null, 'category' => 'Juices | Tea', 'default_price' => 149.00, 'default_product_status' => 'none'],
        ['name' => "Member's Value Banana Chips with Honey", 'description' => '600g', 'image_path' => null, 'category' => 'Snacks | Junkfoods | Nuts', 'default_price' => 289.00, 'default_product_status' => 'none'],
        ['name' => 'KitKat Mini', 'description' => '12x', 'image_path' => null, 'category' => 'Sweets', 'default_price' => 158.00, 'default_product_status' => 'none'],
        ['name' => 'Smart Choice Mixed Nuts Party Snacks', 'description' => '1kg', 'image_path' => null, 'category' => 'Snacks | Junkfoods | Nuts', 'default_price' => 269.00, 'default_product_status' => 'none'],
        ['name' => 'S&R Peanuts Super Garlic', 'description' => '1kg', 'image_path' => null, 'category' => 'Snacks | Junkfoods | Nuts', 'default_price' => 239.00, 'default_product_status' => 'none'],
        ['name' => "Member's Value Dishwashing Liquid Lemon", 'description' => '1gal/3.79L', 'image_path' => null, 'category' => 'Dishwashing', 'default_price' => 299.00, 'default_product_status' => 'none'],
        ['name' => 'Kokola Mochachino Cookies', 'description' => '400g', 'image_path' => null, 'category' => 'Crackers | Biscuits', 'default_price' => 199.00, 'default_product_status' => 'none'],
        ['name' => 'Kokola Milky Vanilla Cookies', 'description' => '400g', 'image_path' => null, 'category' => 'Crackers | Biscuits', 'default_price' => 199.00, 'default_product_status' => 'none'],
        ['name' => 'Nestle Bear Brand Filled Milk', 'description' => '2.4kg', 'image_path' => null, 'category' => 'Coffee | Milk | Creamer', 'default_price' => 748.00, 'default_product_status' => 'none'],
        ['name' => 'Kirkland Signature Original Unsweetened Almond Beverage', 'description' => '946mL', 'image_path' => null, 'category' => 'Coffee | Milk | Creamer', 'default_price' => 104.00, 'default_product_status' => 'none'],
        ['name' => 'Bruna Yogurt Plain Sweetened', 'description' => '4x125g', 'image_path' => null, 'category' => 'Dairy', 'default_price' => 0.00, 'default_product_status' => 'none'],
        ['name' => 'Quaker Rolled Oatmeal', 'description' => '1.2kg', 'image_path' => null, 'category' => 'Cereals', 'default_price' => 0.00, 'default_product_status' => 'none'],
        ['name' => 'Pik-Nik Shoestring Potatoes 50% Less Salt', 'description' => null, 'image_path' => null, 'category' => 'Snacks | Junkfoods | Nuts', 'default_price' => 0.00, 'default_product_status' => 'none'],
        ['name' => 'Pascual Yogurt Greek Style Plain', 'description' => null, 'image_path' => null, 'category' => 'Dairy', 'default_price' => 0.00, 'default_product_status' => 'none'],
    ];

    public function run()
    {
        if (PasabuyProduct::exists()) {
            return;
        }

        $categories = ProductCategory::pluck('id', 'name');

        foreach (self::PRODUCTS as $item) {
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
    }
}
