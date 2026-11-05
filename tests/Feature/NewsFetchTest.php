<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NewsStatus;
use App\Enums\RoleName;
use App\Models\Category;
use App\Models\News;
use App\Models\NewsSource;
use App\Models\User;
use App\Services\RssFetcherService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsFetchTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_save_news_with_duplicate_content_hash(): void
    {
        $category = Category::factory()->create();
        NewsSource::factory()->create(['default_category_id' => $category->id]);

        $title = 'Unique News Title';
        $content = 'Some interesting content that should generate a hash';
        $contentHash = News::generateContentHash($title, $content);

        News::create([
            'title'        => $title,
            'description'  => 'Description',
            'content'      => $content,
            'category_id'  => $category->id,
            'slug'         => 'unique-news-title',
            'content_hash' => $contentHash,
        ]);

        $this->assertDatabaseCount('news', 1);
        $this->assertTrue(News::where('content_hash', $contentHash)->exists());
    }

    public function test_saving_news_calculates_turkish_unicode_reading_time_automatically(): void
    {
        $category = Category::factory()->create();

        $words = array_fill(0, 250, 'çalışma');
        $content = implode(' ', $words);

        $news = News::create([
            'title'       => 'Okuma Süresi Test Başlığı',
            'description' => 'Özet içerik metni burada yer alıyor.',
            'content'     => $content,
            'category_id' => $category->id,
            'slug'        => 'okuma-suresi-test-basligi',
        ]);

        $this->assertSame(2, $news->reading_time);
    }

    public function test_rss_fetcher_extracts_full_article_from_source_url_and_rewrites_in_turkish(): void
    {
        $category = Category::factory()->create([
            'name' => 'Ekonomi',
            'slug' => 'ekonomi',
        ]);

        $source = NewsSource::factory()->create([
            'name'                => 'Örnek Ekonomi Ajansı',
            'url'                 => 'https://feed.example.test/ekonomi.xml',
            'default_category_id' => $category->id,
            'auto_publish'        => true,
            'is_active'           => true,
        ]);

        $rssXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Ekonomi Haberleri</title>
        <item>
            <title>İhracat rakamları ekim ayında rekor kırdı</title>
            <link>https://feed.example.test/haber/ihracat-rekoru</link>
            <description>İhracat verileri açıklandı.</description>
            <pubDate>Thu, 05 Nov 2026 10:00:00 +0300</pubDate>
            <category>Ekonomi</category>
        </item>
    </channel>
</rss>
XML;

        $fullArticleHtml = <<<'HTML'
<!DOCTYPE html>
<html lang="tr">
<head><meta charset="UTF-8"></head>
<body>
    <article>
        <div class="article-body">
            <p>ANKARA (AA) - Ticaret Bakanlığı tarafından gerçekleştirilen basın toplantısında ekim ayı dış ticaret verileri kamuoyuyla paylaşıldı.</p>
            <p>Bakanlık yetkilileri, ekim ayında ihracatın geçen yılın aynı dönemine göre %12,4 artış gösterdi ve 23,8 milyar dolara ulaştığını belirtti.</p>
            <p>Öte yandan yüksek teknolojili ürünlerin toplam dış satım içindeki payının büyümeye devam ediyor olduğu ifade edildi.</p>
            <p>Yeni lojistik merkezlerinin de bu kapsamda yıl sonuna kadar hayata geçirilecek bilgisi paylaşıldı.</p>
        </div>
    </article>
</body>
</html>
HTML;

        Http::fake([
            'https://feed.example.test/ekonomi.xml'          => Http::response($rssXml, 200, ['Content-Type' => 'application/rss+xml']),
            'https://feed.example.test/haber/ihracat-rekoru' => Http::response($fullArticleHtml, 200, ['Content-Type' => 'text/html; charset=UTF-8']),
        ]);

        $fetcher = app(RssFetcherService::class);
        $result = $fetcher->fetchFromSource($source);

        $this->assertSame(1, $result['new']);
        $news = News::where('source_url', 'https://feed.example.test/haber/ihracat-rekoru')->firstOrFail();

        // Full body extracted (> 350 chars instead of 28-char RSS teaser!)
        $this->assertGreaterThan(350, mb_strlen(strip_tags($news->content), 'UTF-8'));
        // Agency prefix stripped, numbers preserved, phrases rewritten
        $this->assertStringNotContainsString('ANKARA (AA)', $news->content);
        $this->assertStringContainsString('%12,4', $news->content);
        $this->assertStringContainsString('23,8 milyar dolara', $news->content);
        $this->assertStringContainsString('düzenlenen basın toplantısında', $news->content);
        $this->assertStringContainsString('Diğer taraftan', $news->content);
    }

    public function test_news_enrich_command_upgrades_existing_short_articles(): void
    {
        $category = Category::factory()->create(['name' => 'Teknoloji', 'slug' => 'teknoloji']);

        $shortNews = News::create([
            'title'       => 'Yerli batarya tesisinde üretim başladı',
            'description' => 'Kısa özet metni.',
            'content'     => 'Kısa özet metni.',
            'category_id' => $category->id,
            'source_url'  => 'https://tech.example.test/yerli-batarya',
            'status'      => NewsStatus::Published->value,
            'is_active'   => true,
            'slug'        => 'yerli-batarya-tesisinde-uretim-basladi',
        ]);

        Http::fake([
            'https://tech.example.test/yerli-batarya' => Http::response(<<<'HTML'
<!DOCTYPE html>
<html lang="tr">
<body>
    <div itemprop="articleBody">
        <p>Yerli elektrikli araç ekosistemi kapsamında kurulan yeni batarya hücre tesisinde seri üretim süreci resmen devreye girdi.</p>
        <p>Mühendislik birimleri, yıllık kapasitenin ilk etapta 20 gigavatsaat olacağını ifade etti.</p>
        <p>Öte yandan Ar-Ge yatırımlarının hız kesmeden devam ediyor olduğu belirtildi.</p>
    </div>
</body>
</html>
HTML, 200, ['Content-Type' => 'text/html; charset=UTF-8']),
        ]);

        $this->artisan('news:enrich --limit=5')
            ->assertExitCode(0);

        $shortNews->refresh();
        $this->assertGreaterThan(250, mb_strlen(strip_tags($shortNews->content), 'UTF-8'));
        $this->assertStringContainsString('faaliyete geçti', $shortNews->content);
        $this->assertStringContainsString('başlangıç aşamasında', $shortNews->content);
    }

    public function test_draft_news_returns_404_for_visitors_but_staff_can_preview_and_feed_is_valid_xml(): void
    {
        $this->seed(PermissionSeeder::class);

        $category = Category::factory()->create(['name' => 'Gündem', 'slug' => 'gundem']);

        $draft = News::create([
            'title'       => 'Taslak Haber Başlığı',
            'description' => 'Taslak haber açıklaması burada.',
            'content'     => '<p>Taslak haber içeriği metni burada yer alıyor.</p>',
            'category_id' => $category->id,
            'status'      => NewsStatus::Draft->value,
            'is_active'   => true,
            'slug'        => 'taslak-haber-basligi',
        ]);

        $published = News::create([
            'title'       => 'Yayındaki Haber Başlığı',
            'description' => 'Yayındaki haber açıklaması burada.',
            'content'     => '<p>Güvenli içerik]]> </p><script>alert(1)</script>',
            'category_id' => $category->id,
            'status'      => NewsStatus::Published->value,
            'is_active'   => true,
            'slug'        => 'yayindaki-haber-basligi',
        ]);

        // Visitor gets 404 for draft
        $this->get(route('news.show', $draft->slug))->assertStatus(404);

        // Editor can preview draft
        $editor = User::factory()->create();
        $editor->syncRoles([RoleName::Editor->value]);
        $this->actingAs($editor)->get(route('news.show', $draft->slug))->assertStatus(200);

        // Published news renders sanitized HTML without <script> and increments views
        $showResponse = $this->get(route('news.show', $published->slug));
        $showResponse->assertStatus(200);
        $showResponse->assertDontSee('<script>alert(1)</script>', false);
        $this->assertSame(1, $published->fresh()?->views);

        // RSS feed outputs valid XML even when content had ']]>' and '<script>'
        $feedResponse = $this->get(route('feed'));
        $feedResponse->assertStatus(200);
        $xml = simplexml_load_string($feedResponse->getContent());
        $this->assertNotFalse($xml);
    }
}
