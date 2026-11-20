<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_save_products_and_only_see_their_own_favorites(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $favoriteProduct = $this->createProduct('PHONE-001', 'Phone One');
        $otherFavoriteProduct = $this->createProduct('PHONE-002', 'Phone Two');
        $otherUser->favoriteProducts()->attach($otherFavoriteProduct);

        $response = $this->actingAs($user)
            ->post(route('products.favorite.store', $favoriteProduct));

        $response->assertRedirect();
        $this->assertDatabaseHas('product_user', [
            'user_id' => $user->id,
            'product_id' => $favoriteProduct->id,
        ]);

        $response = $this->get(route('favorites.index'));

        $response->assertOk()
            ->assertSee('Phone One')
            ->assertDontSee('Phone Two');

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Hapus Phone One dari favorit');
    }

    public function test_users_can_remove_products_from_their_favorites(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct('PHONE-003', 'Phone Three');
        $user->favoriteProducts()->attach($product);

        $response = $this->actingAs($user)
            ->delete(route('products.favorite.destroy', $product));

        $response->assertRedirect();
        $this->assertDatabaseMissing('product_user', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_guest_is_redirected_to_login_from_favorites_page(): void
    {
        $response = $this->get(route('favorites.index'));

        $response->assertRedirect(route('login'));
    }

    private function createProduct(string $code, string $name): Product
    {
        $category = Category::firstOrCreate(['name' => 'Smartphone']);

        return Product::create([
            'category_id' => $category->id,
            'product_code' => $code,
            'name' => $name,
            'price' => 1000000,
            'stock' => 5,
        ]);
    }
}
