<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_a_filtered_csv_with_spreadsheet_safe_product_names(): void
    {
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();
        $category = Category::create(['name' => 'Smartphone']);
        $product = $this->createProduct($category, 'PHONE-001', '=HYPERLINK("bad")');
        $otherProduct = $this->createProduct($category, 'PHONE-002', 'Other Phone');
        Sale::create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'quantity' => 2,
            'unit_price' => 1500000,
            'sold_at' => '2026-11-20 10:30:00',
        ]);
        Sale::create([
            'product_id' => $otherProduct->id,
            'user_id' => $admin->id,
            'quantity' => 1,
            'unit_price' => 500000,
            'sold_at' => '2026-11-20 11:30:00',
        ]);
        Sale::create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'quantity' => 1,
            'unit_price' => 1500000,
            'sold_at' => '2026-11-19 10:30:00',
        ]);

        $response = $this->actingAs($admin)->get(route('sales.export', [
            'date_from' => '2026-11-20',
            'date_to' => '2026-11-20',
            'product_id' => $product->id,
        ]));

        $response->assertDownload()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString('PHONE-001', $csv);
        $this->assertStringContainsString('3000000.00', $csv);
        $this->assertStringNotContainsString('Other Phone', $csv);
    }

    public function test_customer_cannot_download_sales_csv(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->get(route('sales.export'));

        $response->assertForbidden();
    }

    private function createProduct(Category $category, string $code, string $name): Product
    {
        return Product::create([
            'category_id' => $category->id,
            'product_code' => $code,
            'name' => $name,
            'price' => 1500000,
            'stock' => 10,
        ]);
    }
}
