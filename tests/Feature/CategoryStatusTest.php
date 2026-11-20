<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_offers_both_category_statuses(): void
    {
        $this->authenticateAsAdmin();

        $response = $this->get(route('categories.create'));

        $response->assertOk()
            ->assertSee('name="status"', false)
            ->assertSee('value="active"', false)
            ->assertSee('value="inactive"', false);
    }

    public function test_edit_form_selects_the_categories_current_status(): void
    {
        $this->authenticateAsAdmin();

        $category = Category::create([
            'name' => 'Audio',
            'description' => 'Kategori perangkat audio.',
            'status' => 'inactive',
        ]);

        $response = $this->get(route('categories.edit', $category));

        $response->assertOk()
            ->assertSee('value="inactive" selected', false);
    }

    public function test_category_list_displays_active_and_inactive_statuses(): void
    {
        $this->authenticateAsAdmin();

        Category::create(['name' => 'Audio', 'status' => 'active']);
        Category::create(['name' => 'Gaming', 'status' => 'inactive']);

        $response = $this->get(route('categories.index'));

        $response->assertOk()
            ->assertSee('Aktif')
            ->assertSee('Tidak Aktif');
    }

    public function test_category_can_be_created_with_inactive_status(): void
    {
        $this->authenticateAsAdmin();

        $response = $this->post(route('categories.store'), [
            'name' => 'Audio',
            'description' => 'Kategori perangkat audio.',
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'name' => 'Audio',
            'status' => 'inactive',
        ]);
    }

    public function test_category_status_can_be_changed_when_edited(): void
    {
        $this->authenticateAsAdmin();

        $category = Category::create([
            'name' => 'Audio',
            'description' => 'Kategori perangkat audio.',
            'status' => 'active',
        ]);

        $response = $this->put(route('categories.update', $category), [
            'name' => 'Audio',
            'description' => 'Kategori perangkat audio.',
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'status' => 'inactive',
        ]);
    }

    public function test_category_rejects_an_unsupported_status(): void
    {
        $this->authenticateAsAdmin();

        $response = $this->post(route('categories.store'), [
            'name' => 'Audio',
            'description' => 'Kategori perangkat audio.',
            'status' => 'paused',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseMissing('categories', [
            'name' => 'Audio',
        ]);
    }

    private function authenticateAsAdmin(): void
    {
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();

        $this->actingAs($admin);
    }
}
