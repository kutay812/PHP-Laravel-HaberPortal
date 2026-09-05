<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\MainCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Super Admin']);
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Editor']);
        Role::create(['name' => 'User']);
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create();
        // Regular user has 'User' role by default

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_admin_user_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $mainCategory = MainCategory::create([
            'name' => 'Ana Kategori',
            'slug' => 'ana-kategori',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name'             => 'Yeni Test Kategorisi',
            'description'      => 'Açıklama metni',
            'main_category_id' => $mainCategory->id,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', [
            'name'             => 'Yeni Test Kategorisi',
            'main_category_id' => $mainCategory->id,
        ]);
    }

    public function test_admin_can_delete_profile_image(): void
    {
        $admin = User::factory()->create([
            'profile_image' => 'profile-image/test.jpg',
        ]);
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->delete(route('admin.profile.image.delete'));

        $response->assertRedirect(route('admin.profile'));
        $this->assertNull($admin->fresh()->profile_image);
    }
}
