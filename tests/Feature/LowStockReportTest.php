<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowStockReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_low_stock_products_and_open_restock_form(): void
    {
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();
        $category = Category::create(['name' => 'Smartphone']);
        $lowStockProduct = $this->createProduct($category, 'PHONE-LOW', 'Phone Low', 5);
        $this->createProduct($category, 'PHONE-OK', 'Phone OK', 6);

        $response = $this->actingAs($admin)->get(route('inventory.low-stock', ['threshold' => 5]));

        $response->assertOk()
            ->assertSee('Phone Low')
            ->assertDontSee('Phone OK')
            ->assertSee(route('products.edit', $lowStockProduct));
    }

    public function test_guest_is_redirected_to_login_from_low_stock_report(): void
    {
        $response = $this->get(route('inventory.low-stock'));

        $response->assertRedirect(route('login'));
    }

    public function test_low_stock_report_rejects_non_numeric_threshold(): void
    {
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();

        $response = $this->actingAs($admin)
            ->from(route('inventory.low-stock'))
            ->get(route('inventory.low-stock', ['threshold' => 'low']));

        $response->assertRedirect(route('inventory.low-stock'))
            ->assertSessionHasErrors('threshold');
    }

    private function createProduct(Category $category, string $code, string $name, int $stock): Product
    {
        return Product::create([
            'category_id' => $category->id,
            'product_code' => $code,
            'name' => $name,
            'price' => 1000000,
            'stock' => $stock,
        ]);
    }
}
