<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NewsStatus;
use App\Enums\RoleName;
use App\Jobs\FetchNewsJob;
use App\Models\Category;
use App\Models\MainCategory;
use App\Models\News;
use App\Models\NewsSource;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_homepage_renders_brand_logos(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('images/logo-light.png', false);
        $response->assertSee('images/logo-dark.png', false);
    }

    public function test_guest_visiting_admin_redirects_to_admin_login_and_lands_on_panel_immediately_after_login(): void
    {
        $admin = User::factory()->create([
            'email'    => 'adminlogin@example.com',
            'password' => 'SecretPass123!',
        ]);
        $admin->syncRoles([RoleName::SuperAdmin->value]);

        // 1. Guest hits /admin -> redirected to /admin/login (not /login)
        $guestResponse = $this->get('/admin');
        $guestResponse->assertRedirect(route('admin.login'));

        // 2. Admin submits /admin/login -> 303 redirect directly to /admin
        $loginResponse = $this->post(route('admin.login.post'), [
            'email'    => 'adminlogin@example.com',
            'password' => 'SecretPass123!',
        ]);
        $loginResponse->assertStatus(303);
        $loginResponse->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);

        // 3. First GET /admin immediately renders panel with anti-cache headers (no refresh needed)
        $panelResponse = $this->get('/admin');
        $panelResponse->assertStatus(200);
        $panelResponse->assertSee('Yönetim Paneli');
        $this->assertStringContainsString('no-store', (string) $panelResponse->headers->get('Cache-Control'));

        // 4. Authenticated admin visiting /admin/login is redirected straight to /admin
        $this->get(route('admin.login'))->assertRedirect(route('admin.index'));

        // 5. Logout button works cleanly
        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_regular_user_cannot_access_admin_dashboard_or_admin_login(): void
    {
        $user = User::factory()->create([
            'email'    => 'regular@example.com',
            'password' => 'SecretPass123!',
        ]);

        $this->actingAs($user)->get('/admin')->assertStatus(403);

        auth()->logout();

        $loginAttempt = $this->post(route('admin.login.post'), [
            'email'    => 'regular@example.com',
            'password' => 'SecretPass123!',
        ]);
        $loginAttempt->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_user_can_access_admin_dashboard_and_clear_cache(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles([RoleName::Admin->value]);

        $this->actingAs($admin)->get('/admin')->assertStatus(200);
        $this->actingAs($admin)->post(route('admin.clear-cache'))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_admin_news_buttons_create_show_rss_update_search_and_delete_work(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $admin->syncRoles([RoleName::Admin->value]);
        $category = Category::factory()->create(['name' => 'Teknoloji', 'slug' => 'teknoloji']);

        // 1. Create news with new_image, flags, tags, and draft/inactive state
        $storeResponse = $this->actingAs($admin)->post(route('admin.news.store'), [
            'title'          => 'Yeni Nesil İşlemci Mimarisi Tanıtıldı',
            'description'    => 'Yerli teknoloji zirvesinde yeni nesil işlemci mimarisi detaylarıyla paylaşıldı.',
            'content'        => '<p>Yerli teknoloji zirvesinde tanıtılan yeni nesil işlemci mimarisi yüksek enerji verimliliği sunuyor.</p>',
            'category_id'    => $category->id,
            'new_image'      => UploadedFile::fake()->image('chip.jpg', 600, 400),
            'is_active'      => '0',
            'is_featured'    => '1',
            'is_breaking'    => '1',
            'is_editor_pick' => '0',
            'tags'           => 'işlemci, teknoloji',
        ]);

        $storeResponse->assertRedirect(route('admin.news.index'));

        $news = News::where('title', 'Yeni Nesil İşlemci Mimarisi Tanıtıldı')->firstOrFail();
        $this->assertNotNull($news->image_id);
        $this->assertFalse($news->is_active);
        $this->assertSame(NewsStatus::Draft, $news->status);
        $this->assertTrue($news->is_featured);
        $this->assertTrue($news->is_breaking);
        $this->assertCount(2, $news->tags);

        // 2. Index renders status badge properly with BackedEnum
        $this->actingAs($admin)->get(route('admin.news.index'))
            ->assertStatus(200)
            ->assertSee('Taslak / Pasif');

        // 3. RSS news without author (user_id = null) opens in admin.news.show without 500 crash
        $rssNews = News::create([
            'title'       => 'Otonom RSS Haberi Detay Testi',
            'description' => 'RSS kaynağından gelen yazarsız haber özeti.',
            'content'     => '<p>RSS kaynağından gelen yazarsız haberin tam metni burada yer alıyor.</p>',
            'category_id' => $category->id,
            'user_id'     => null,
            'is_active'   => true,
            'status'      => NewsStatus::Published->value,
            'slug'        => 'otonom-rss-haberi-detay-testi',
        ]);

        $this->actingAs($admin)->get(route('admin.news.show', $rssNews))
            ->assertStatus(200)
            ->assertSee('Otonom Bot');

        // 4. Update news to active -> status becomes Published
        $this->actingAs($admin)->put(route('admin.news.update', $news), [
            'title'          => 'Yeni Nesil İşlemci Mimarisi Güncellendi',
            'description'    => 'Yerli teknoloji zirvesinde yeni nesil işlemci mimarisi detaylarıyla paylaşıldı.',
            'content'        => '<p>Güncellenmiş içerik metni burada güvenli biçimde yer almaktadır.</p>',
            'category_id'    => $category->id,
            'is_active'      => '1',
            'is_featured'    => '1',
            'is_breaking'    => '0',
            'is_editor_pick' => '1',
            'tags'           => 'işlemci',
        ])->assertRedirect(route('admin.news.index'));

        $news->refresh();
        $this->assertTrue($news->is_active);
        $this->assertSame(NewsStatus::Published, $news->status);
        $this->assertFalse($news->is_breaking);
        $this->assertTrue($news->is_editor_pick);

        // 5. Autocomplete search endpoint works
        $this->actingAs($admin)->getJson(route('admin.news.search', ['search' => 'İşlemci']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'items');

        // 6. Delete button removes news
        $this->actingAs($admin)->delete(route('admin.news.destroy', $news))
            ->assertRedirect(route('admin.news.index'));
        $this->assertSoftDeleted('news', ['id' => $news->id]);
    }

    public function test_admin_can_create_update_and_safely_delete_categories(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles([RoleName::Admin->value]);

        $mainCategory = MainCategory::create([
            'name' => 'Ana Kategori',
            'slug' => 'ana-kategori',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name'             => 'Yeni Test Kategorisi',
            'title'            => 'SEO Test Başlığı',
            'description'      => 'Açıklama metni',
            'main_category_id' => $mainCategory->id,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $category = Category::where('name', 'Yeni Test Kategorisi')->firstOrFail();
        $this->assertSame('SEO Test Başlığı', $category->title);

        // Delete empty category succeeds
        $this->actingAs($admin)->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');
    }

    public function test_editor_can_view_categories_but_cannot_create_or_delete_category(): void
    {
        $editor = User::factory()->create();
        $editor->syncRoles([RoleName::Editor->value]);

        $this->actingAs($editor)
            ->get(route('admin.categories.index'))
            ->assertStatus(200);

        $this->actingAs($editor)
            ->post(route('admin.categories.store'), [
                'name' => 'Yetkisiz Kategori',
            ])
            ->assertStatus(403);
    }

    public function test_admin_sources_and_tags_buttons_work_including_unchecked_switches(): void
    {
        Queue::fake();

        $admin = User::factory()->create();
        $admin->syncRoles([RoleName::Admin->value]);
        $category = Category::factory()->create();

        // Create RSS source with unchecked switches (0)
        $this->actingAs($admin)->post(route('admin.sources.store'), [
            'name'                   => 'Test Haber Kaynağı',
            'url'                    => 'https://feed.example.test/rss.xml',
            'type'                   => 'rss',
            'reliability_score'      => 85,
            'default_category_id'    => $category->id,
            'fetch_interval_minutes' => 30,
            'is_active'              => '0',
            'auto_publish'           => '0',
        ])->assertRedirect(route('admin.sources.index'));

        $source = NewsSource::where('name', 'Test Haber Kaynağı')->firstOrFail();
        $this->assertFalse($source->is_active);
        $this->assertFalse($source->auto_publish);

        // Per-source "Şimdi Çek" button
        $this->actingAs($admin)->post(route('admin.sources.fetch', $source))
            ->assertRedirect()
            ->assertSessionHas('success');
        Queue::assertPushed(FetchNewsJob::class, static fn (FetchNewsJob $job): bool => $job->newsSourceId === $source->id && $job->force === true);

        // Delete source button
        $this->actingAs($admin)->delete(route('admin.sources.destroy', $source))
            ->assertRedirect(route('admin.sources.index'));
        $this->assertDatabaseMissing('news_sources', ['id' => $source->id]);

        // Tags CRUD buttons
        $this->actingAs($admin)->post(route('admin.tags.store'), [
            'name'  => 'Yapay Zeka',
            'color' => '#2563eb',
        ])->assertRedirect(route('admin.tags.index'));

        $tag = Tag::where('name', 'Yapay Zeka')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.tags.update', $tag), [
            'name'  => 'Üretken Yapay Zeka',
            'color' => '#dc2626',
        ])->assertRedirect(route('admin.tags.index'));

        $this->actingAs($admin)->delete(route('admin.tags.destroy', $tag))
            ->assertRedirect(route('admin.tags.index'));
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_admin_cannot_escalate_privileges_to_super_admin(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles([RoleName::Admin->value]);

        $superAdminRole = Role::where('name', RoleName::SuperAdmin->value)->firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name'                  => 'Escalated User',
            'email'                 => 'escalated@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'roles'                 => [$superAdminRole->id],
        ]);

        $response->assertSessionHasErrors('roles.0');
        $this->assertDatabaseMissing('users', ['email' => 'escalated@example.com']);
    }

    public function test_super_admin_editing_own_user_record_preserves_super_admin_role(): void
    {
        $superAdmin = User::factory()->create([
            'name'  => 'İlk İsim',
            'email' => 'super@example.com',
        ]);
        $superAdmin->syncRoles([RoleName::SuperAdmin->value]);

        // Even if disabled checkbox omitted `roles` from POST payload, Super Admin keeps their role
        $response = $this->actingAs($superAdmin)->put(route('admin.users.update', $superAdmin), [
            'name'  => 'Güncel İsim',
            'email' => 'super@example.com',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $superAdmin->refresh();
        $this->assertSame('Güncel İsim', $superAdmin->name);
        $this->assertTrue($superAdmin->hasRole(RoleName::SuperAdmin->value));
    }

    public function test_admin_created_editor_only_has_selected_role_without_leftover_user_role(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles([RoleName::Admin->value]);

        $editorRole = Role::where('name', RoleName::Editor->value)->firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name'                  => 'Yeni Editör',
            'email'                 => 'editor@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'roles'                 => [$editorRole->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $created = User::where('email', 'editor@example.com')->firstOrFail();
        $this->assertTrue($created->hasRole(RoleName::Editor->value));
        $this->assertFalse($created->hasRole(RoleName::User->value));
    }

    public function test_manual_fetch_news_dispatches_background_queue_job(): void
    {
        Queue::fake();

        $admin = User::factory()->create();
        $admin->syncRoles([RoleName::Admin->value]);

        $response = $this->actingAs($admin)->post(route('admin.fetch-news'));

        $response->assertRedirect();
        Queue::assertPushed(FetchNewsJob::class, static fn (FetchNewsJob $job): bool => $job->newsSourceId === null && $job->force === true);
    }

    public function test_admin_profile_update_changes_password_and_deletes_profile_image(): void
    {
        $admin = User::factory()->create([
            'password'      => 'OldPassword123!',
            'profile_image' => 'profile-image/test.jpg',
        ]);
        $admin->syncRoles([RoleName::Admin->value]);

        $this->actingAs($admin)->put(route('admin.profile.update'), [
            'name'                  => 'Güncel Admin',
            'email'                 => $admin->email,
            'current_password'      => 'OldPassword123!',
            'password'              => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ])->assertRedirect(route('admin.profile'))
          ->assertSessionHas('success');

        $admin->refresh();
        $this->assertSame('Güncel Admin', $admin->name);
        $this->assertTrue(Hash::check('NewPassword456!', $admin->password));

        $this->actingAs($admin)->delete(route('admin.profile.image.delete'))
            ->assertRedirect(route('admin.profile'));
        $this->assertNull($admin->fresh()?->profile_image);
    }
}
