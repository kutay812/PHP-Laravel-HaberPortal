<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NewsStatus;
use App\Enums\RoleName;
use App\Jobs\FetchNewsJob;
use App\Mail\AdminPasswordResetMail;
use App\Models\Category;
use App\Models\News;
use App\Models\NewsSource;
use App\Models\User;
use App\Services\RssFetcherService;
use App\Services\UrlSecurityService;
use Database\Seeders\PermissionSeeder;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Psr7\Uri as Psr7Uri;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityAndCostAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_unauthenticated_anon_requests_cannot_call_expensive_or_private_endpoints(): void
    {
        $category = Category::factory()->create(['slug' => 'gundem']);
        $source = NewsSource::create([
            'name'                   => 'Test Kaynak',
            'url'                    => 'https://feed.example.test/rss.xml',
            'type'                   => 'rss',
            'reliability_score'      => 90,
            'default_category_id'    => $category->id,
            'fetch_interval_minutes' => 30,
            'is_active'              => true,
            'auto_publish'           => true,
        ]);
        $publishedNews = News::create([
            'title'       => 'Yayında Olan Haber',
            'description' => 'Kamuya açık yayınlanmış haber özeti.',
            'content'     => '<p>Kamuya açık haber metni.</p>',
            'category_id' => $category->id,
            'is_active'   => true,
            'status'      => NewsStatus::Published,
            'slug'        => 'yayinda-olan-haber',
        ]);
        $draftNews = News::create([
            'title'       => 'Gizli Taslak Haber Başlığı',
            'description' => 'Gizli taslak özeti dışarı sızmamalı.',
            'content'     => '<p>Gizli taslak metni.</p>',
            'category_id' => $category->id,
            'is_active'   => false,
            'status'      => NewsStatus::Draft,
            'slug'        => 'gizli-taslak-haber',
        ]);

        // Unauthenticated POST/PUT/GET to protected & expensive routes
        $this->post(route('admin.fetch-news'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.sources.fetch', $source))->assertRedirect(route('admin.login'));
        $this->post(route('news.store'), [])->assertRedirect(route('login'));
        $this->put(route('news.update', $publishedNews->id), [])->assertRedirect(route('login'));
        $this->post(route('comments.store', $publishedNews), ['content' => 'Anonim yorum'])->assertRedirect(route('login'));
        $this->get(route('admin.users.search', ['search' => 'admin']))->assertRedirect(route('admin.login'));
        $this->getJson('/api/user')->assertStatus(401);

        // Draft/unpublished data never leaks via public pages, search, sitemap, or RSS feed
        $this->get(route('news.show', $draftNews->slug))->assertStatus(404);
        $this->get(route('home', ['search' => 'Gizli Taslak']))
            ->assertStatus(200)
            ->assertDontSee('Gizli Taslak Haber Başlığı');
        $this->get(route('sitemap'))
            ->assertStatus(200)
            ->assertSee('yayinda-olan-haber')
            ->assertDontSee('gizli-taslak-haber');
        $this->get(route('feed'))
            ->assertStatus(200)
            ->assertDontSee('Gizli Taslak Haber Başlığı');

        // Authenticated API never leaks password hash or remember_token
        $user = User::factory()->create(['remember_token' => 'secret-remember-token']);
        Sanctum::actingAs($user);
        $apiResp = $this->getJson('/api/user')->assertStatus(200);
        $apiResp->assertJsonMissingPath('password');
        $apiResp->assertJsonMissingPath('remember_token');
    }

    public function test_regular_user_is_blocked_at_route_layer_from_writing_news(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($user)->post(route('news.store'), [
            'title'       => 'Yetkisiz Haber Ekleme Denemesi',
            'description' => 'Normal kullanıcı haber ekleyememelidir.',
            'content'     => '<p>Bu içerik kaydedilmemelidir çünkü yetki yoktur.</p>',
            'category_id' => $category->id,
        ])->assertStatus(403);
    }

    public function test_rate_limits_and_daily_quotas_enforce_per_user_and_per_ip_caps(): void
    {
        Queue::fake();

        config([
            'security.rate_limits.rss_fetch_per_minute' => 2,
            'security.daily_quotas.rss_fetch.per_actor' => 10,
            'security.daily_quotas.comments_write.per_actor' => 2,
            'security.daily_quotas.public_search.per_actor' => 2,
        ]);

        $admin = User::factory()->create();
        $admin->syncRoles([RoleName::Admin->value]);

        // 1. Per-minute rate limit on expensive RSS fetch endpoint (max 2/min)
        $this->actingAs($admin)->post(route('admin.fetch-news'))
            ->assertRedirect()
            ->assertHeader('X-Daily-Quota-Remaining', '9');
        $this->actingAs($admin)->post(route('admin.fetch-news'))
            ->assertRedirect()
            ->assertHeader('X-Daily-Quota-Remaining', '8');
        $this->actingAs($admin)->post(route('admin.fetch-news'))
            ->assertStatus(429);

        // 2. Daily hard quota on comments enforces BOTH per-user AND per-IP (switching account on same IP still blocked)
        $category = Category::factory()->create();
        $news = News::create([
            'title'       => 'Kota Test Haberi',
            'description' => 'Günlük yorum kotası testi için haber özeti.',
            'content'     => '<p>Günlük yorum kotası testi için haber metni.</p>',
            'category_id' => $category->id,
            'is_active'   => true,
            'status'      => NewsStatus::Published,
            'slug'        => 'kota-test-haberi',
        ]);

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA)->post(route('comments.store', $news), ['content' => 'Birinci yorum metni'])
            ->assertRedirect()
            ->assertHeader('X-Daily-Quota-Remaining', '1');
        $this->actingAs($userA)->post(route('comments.store', $news), ['content' => 'İkinci yorum metni'])
            ->assertRedirect()
            ->assertHeader('X-Daily-Quota-Remaining', '0');

        // 3rd comment by same user -> 429
        $this->actingAs($userA)->post(route('comments.store', $news), ['content' => 'Üçüncü yorum engellenmeli'])
            ->assertStatus(429);

        // Switching to a brand-new user account from the SAME IP address -> still 429 due to IP daily quota!
        $this->actingAs($userB)->post(route('comments.store', $news), ['content' => 'Aynı IP farklı hesap denemesi'])
            ->assertStatus(429);

        // 3. Public search daily quota blocks excessive searches while keeping homepage open
        $this->get(route('home', ['search' => 'ekonomi']))->assertStatus(200);
        $this->get(route('home', ['search' => 'spor']))->assertStatus(200);
        $this->get(route('home', ['search' => 'teknoloji']))->assertStatus(429);
        $this->get(route('home'))->assertStatus(200);
    }

    public function test_loop_redirect_ssrf_and_circuit_breaker_protections(): void
    {
        config(['app.url' => 'https://gecehaber.example']);

        $security = app(UrlSecurityService::class);

        // 1. Self-application domain blocked to prevent RSS self-ingestion loop
        $this->assertFalse($security->isSafePublicUrl('https://gecehaber.example/feed'));
        $this->assertTrue($security->isSafePublicUrl('https://external.example/rss.xml'));

        // 2. Redirect chain blocks SSRF hop to internal IP / cloud metadata
        $options = $security->safeRedirectOptions(3);
        /** @var callable $onRedirect */
        $onRedirect = $options['allow_redirects']['on_redirect'];

        $ssrfThrown = false;
        try {
            $onRedirect(
                new Psr7Request('GET', 'https://external.example/article'),
                new Psr7Response(302),
                new Psr7Uri('http://169.254.169.254/latest/meta-data/')
            );
        } catch (\RuntimeException) {
            $ssrfThrown = true;
        }
        $this->assertTrue($ssrfThrown, 'Redirect to cloud metadata IP must throw RuntimeException.');

        // 3. Redirect chain blocks A -> B -> A loop
        $loopOptions = $security->safeRedirectOptions(3);
        /** @var callable $loopCallback */
        $loopCallback = $loopOptions['allow_redirects']['on_redirect'];
        $loopCallback(
            new Psr7Request('GET', 'https://a.example/step1'),
            new Psr7Response(302),
            new Psr7Uri('https://b.example/step2')
        );

        $loopThrown = false;
        try {
            $loopCallback(
                new Psr7Request('GET', 'https://b.example/step2'),
                new Psr7Response(302),
                new Psr7Uri('https://a.example/step1')
            );
        } catch (\RuntimeException) {
            $loopThrown = true;
        }
        $this->assertTrue($loopThrown, 'Cyclic HTTP redirect loop must throw RuntimeException.');

        // 4. RSS Circuit Breaker automatically disables failing source after 10 consecutive failures
        Http::fake([
            'https://failing.example.test/rss.xml' => Http::response('Server Error', 500),
        ]);

        $category = Category::factory()->create();
        $failingSource = NewsSource::create([
            'name'                   => 'Bozuk Kaynak',
            'url'                    => 'https://failing.example.test/rss.xml',
            'type'                   => 'rss',
            'reliability_score'      => 50,
            'default_category_id'    => $category->id,
            'fetch_interval_minutes' => 15,
            'failed_fetches'         => 9,
            'is_active'              => true,
            'auto_publish'           => true,
        ]);

        app(RssFetcherService::class)->fetchFromSource($failingSource);
        $failingSource->refresh();
        $this->assertSame(10, $failingSource->failed_fetches);
        $this->assertFalse($failingSource->is_active, 'Circuit breaker should deactivate source at 10 failures.');

        // 5. Queue jobs enforce uniqueness and finite retry bounds (no infinite retries)
        $fetchJob = new FetchNewsJob(null, true);
        $this->assertInstanceOf(ShouldBeUnique::class, $fetchJob);
        $this->assertSame(2, $fetchJob->tries);
        $this->assertSame(1, $fetchJob->maxExceptions);

        $resetMail = new AdminPasswordResetMail('https://example.test/reset');
        $this->assertSame(2, $resetMail->tries);
        $this->assertSame(1, $resetMail->maxExceptions);
    }
}

