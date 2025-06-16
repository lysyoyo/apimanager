<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private function getAdminToken(): string
    {
        $admin = User::factory()->create(['role' => 1]);
        return $admin->createToken('admin')->plainTextToken;
    }

    private function getUserToken(): string
    {
        $user = User::factory()->create(['role' => 0]);
        return $user->createToken('user')->plainTextToken;
    }

    /** @test */
    public function anyone_can_list_categories()
    {
        Category::factory()->count(3)->create();

        $this->getJson('/api/categories')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function admin_can_create_category()
    {
        $token = $this->getAdminToken();

        $this->withToken($token)
            ->postJson('/api/categories', [
                'name' => 'Musique'
            ])
            ->assertStatus(201)
            ->assertJson(['name' => 'Musique']);
    }

    /** @test */
    public function category_name_must_be_unique()
    {
        $token = $this->getAdminToken();
        Category::factory()->create(['name' => 'Sport']);

        $this->withToken($token)
            ->postJson('/api/categories', ['name' => 'Sport'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function anyone_can_view_category_by_id()
    {
        $category = Category::factory()->create();

        $this->getJson("/api/categories/{$category->id}")
            ->assertStatus(200)
            ->assertJson(['id' => $category->id, 'name' => $category->name]);
    }

    /** @test */
    public function admin_can_update_category()
    {
        $token = $this->getAdminToken();
        $category = Category::factory()->create(['name' => 'Tech']);

        $this->withToken($token)
            ->putJson("/api/categories/{$category->id}", ['name' => 'Informatique'])
            ->assertStatus(200)
            ->assertJson(['name' => 'Informatique']);
    }

    /** @test */
    public function admin_can_delete_category()
    {
        $token = $this->getAdminToken();
        $category = Category::factory()->create();

        $this->withToken($token)
            ->deleteJson("/api/categories/{$category->id}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Catégorie supprimée']);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    /** @test */
    public function not_found_returns_404()
    {
        $this->getJson('/api/categories/999999')
            ->assertStatus(404);
    }
}
