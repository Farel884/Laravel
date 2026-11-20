<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_sales_report(): void
    {
        $this->authenticateAsAdmin();

        $response = $this->get(route('sales.index'));

        $response->assertOk()
            ->assertSee('Laporan Penjualan')
            ->assertSee('Catat Penjualan');
    }

    public function test_customer_cannot_open_sales_report(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->get(route('sales.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_record_sale_and_stock_is_decremented(): void
    {
        $admin = $this->authenticateAsAdmin();
        $category = Category::create(['name' => 'Smartphone', 'status' => 'active']);
        $product = Product::create([
            'category_id' => $category->id,
            'product_code' => 'PHONE-001',
            'name' => 'Phone One',
            'brand' => 'Example',
            'price' => 3000000,
            'stock' => 10,
        ]);

        $response = $this->post(route('sales.store'), [
            'product_id' => $product->id,
            'quantity' => 3,
            'sold_at' => '2026-11-20 10:30:00',
        ]);

        $response->assertRedirect(route('sales.index'));
        $this->assertDatabaseHas('sales', [
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'quantity' => 3,
            'unit_price' => '3000000.00',
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 7,
        ]);
    }

    public function test_sale_is_rejected_when_quantity_exceeds_stock(): void
    {
        $this->authenticateAsAdmin();
        $category = Category::create(['name' => 'Tablet', 'status' => 'active']);
        $product = Product::create([
            'category_id' => $category->id,
            'product_code' => 'TABLET-001',
            'name' => 'Tablet One',
            'brand' => 'Example',
            'price' => 2000000,
            'stock' => 2,
        ]);

        $response = $this->from(route('sales.create'))->post(route('sales.store'), [
            'product_id' => $product->id,
            'quantity' => 3,
            'sold_at' => '2026-11-20 10:30:00',
        ]);

        $response->assertRedirect(route('sales.create'))
            ->assertSessionHasErrors('quantity');
        $this->assertDatabaseMissing('sales', ['product_id' => $product->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 2]);
    }

    private function authenticateAsAdmin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        return $admin;
    }
}
