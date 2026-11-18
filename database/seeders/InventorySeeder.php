<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryData = [
            ['name' => 'Smartphone', 'products' => 35, 'stock' => 450],
            ['name' => 'Laptop', 'products' => 100, 'stock' => 1700],
            ['name' => 'Tablet', 'products' => 50, 'stock' => 900],
            ['name' => 'Audio', 'products' => 115, 'stock' => 2200],
            ['name' => 'Aksesoris', 'products' => 75, 'stock' => 1400],
            ['name' => 'Smartwatch', 'products' => 90, 'stock' => 2600],
            ['name' => 'Gaming', 'products' => 35, 'stock' => 750],
        ];
        $brands = ['Samsung', 'Apple', 'Xiaomi', 'Lenovo', 'Sony', 'Asus', 'Logitech'];
        $now = now();

        DB::transaction(function () use ($categoryData, $brands, $now): void {
            $categoryIds = [];

            foreach ($categoryData as $category) {
                $categoryId = DB::table('categories')
                    ->where('name', $category['name'])
                    ->value('id');

                if ($categoryId === null) {
                    $categoryId = DB::table('categories')->insertGetId([
                        'name' => $category['name'],
                        'description' => "Produk kategori {$category['name']}.",
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    DB::table('categories')->where('id', $categoryId)->update([
                        'description' => "Produk kategori {$category['name']}.",
                        'updated_at' => $now,
                    ]);
                }

                $categoryIds[$category['name']] = $categoryId;
            }

            $products = [];
            $productIndex = 1;
            $lowStockProductsRemaining = 15;

            foreach ($categoryData as $category) {
                $lowStockCount = min($lowStockProductsRemaining, $category['products']);
                $regularProductCount = $category['products'] - $lowStockCount;
                $regularStockTotal = $category['stock'] - ($lowStockCount * 5);
                $regularStockPerProduct = intdiv($regularStockTotal, $regularProductCount);
                $extraStockProducts = $regularStockTotal % $regularProductCount;

                for ($categoryProductIndex = 0; $categoryProductIndex < $category['products']; $categoryProductIndex++) {
                    $brand = $brands[($productIndex - 1) % count($brands)];
                    $isLowStock = $categoryProductIndex < $lowStockCount;
                    $regularProductIndex = $categoryProductIndex - $lowStockCount;

                    $products[] = [
                        'category_id' => $categoryIds[$category['name']],
                        'product_code' => sprintf('INV-%04d', $productIndex),
                        'name' => sprintf('%s %s Model %03d', $brand, $category['name'], $productIndex),
                        'brand' => $brand,
                        'price' => (($productIndex % 25) + 1) * 125000,
                        'stock' => $isLowStock
                            ? 5
                            : $regularStockPerProduct + ($regularProductIndex < $extraStockProducts ? 1 : 0),
                        'description' => "Produk {$category['name']} dari {$brand}.",
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $productIndex++;
                }

                $lowStockProductsRemaining -= $lowStockCount;
            }

            DB::table('products')->upsert(
                $products,
                ['product_code'],
                ['category_id', 'name', 'brand', 'price', 'stock', 'description', 'updated_at']
            );
        });
    }
}
