<?php

namespace Database\Seeders;

use App\Models\PasabuyProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductNameBrandPrefixSeeder extends Seeder
{
    public function run()
    {
        PasabuyProduct::with('brand')
            ->whereNotNull('brand_id')
            ->chunkById(100, function ($products) {
                foreach ($products as $product) {
                    $brandName = $product->brand?->name;

                    if (! $brandName) {
                        continue;
                    }

                    $firstWord = Str::of($product->name)->trim()->explode(' ')->first();

                    if (Str::lower($firstWord) === Str::lower($brandName)) {
                        continue;
                    }

                    $product->name = "{$brandName} {$product->name}";
                    $product->save();
                }
            });
    }
}
