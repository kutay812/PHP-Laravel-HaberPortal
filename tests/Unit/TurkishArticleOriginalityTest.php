<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ArticleExtractorService;
use App\Services\HtmlSanitizerService;
use App\Services\TurkishArticleRewriterService;
use App\Services\UrlSecurityService;
use Tests\TestCase;

class TurkishArticleOriginalityTest extends TestCase
{
    public function test_rewriter_paraphrases_turkish_journalistic_prose_while_preserving_quotes_numbers_and_entities(): void
    {
        $rewriter = app(TurkishArticleRewriterService::class);

        $rawContent = <<<'TXT'
ANKARA (AA) - Merkez Bankası tarafından yapılan yazılı açıklamada faiz politikasının yakından takip ediliyor olduğu belirtildi.
Yetkililer, "Enflasyon %14,5 seviyesine gerilerken ihracat 25,4 milyar dolar oldu" değerlendirmesinde bulundu ve sürecin büyük önem taşıyor olduğunu vurguladı.
Öte yandan yeni destek paketinin ilk etapta KOBİ'ler için hayata geçirildi ve üretime katkı sağlamaya devam ediyor.
Bunun yanı sıra www.ornekhaber.com internet sitesinde yayınlanan yazı, haber ve fotoğrafların her türlü telif hakkı saklıdır. İzin alınmadan iktibas edilemez.
TXT;

        $result = $rewriter->rewrite(
            'Merkez Bankası yeni destek paketini duyurdu',
            $rawContent,
            'ANKARA (AA) - Merkez Bankası yeni adımları paylaştı.'
        );

        // Agency prefix & copyright boilerplate must be stripped
        $this->assertStringNotContainsString('ANKARA (AA)', $result['content']);
        $this->assertStringNotContainsString('iktibas edilemez', $result['content']);
        $this->assertStringNotContainsString('telif hakkı', $result['content']);

        // Direct quote, percentages, numbers, and proper nouns must be preserved verbatim
        $this->assertStringContainsString('Enflasyon %14,5 seviyesine gerilerken ihracat 25,4 milyar dolar oldu', $result['content']);
        $this->assertStringContainsString('Merkez Bankası', $result['content']);

        // Journalistic phrases must be transformed cleanly with valid Turkish grammar
        $this->assertStringContainsString('olduğu kaydedildi', $result['content']);
        $this->assertStringContainsString('olduğunun altını çizdi', $result['content']);
        $this->assertStringContainsString('Diğer taraftan', $result['content']);
        $this->assertStringContainsString('başlangıç aşamasında', $result['content']);
        $this->assertStringContainsString('uygulamaya alındı', $result['content']);
        $this->assertStringContainsString('sağlamayı sürdürüyor', $result['content']);

        // Structured HTML lead must be generated
        $this->assertStringContainsString('<p><strong>', $result['content']);
    }

    public function test_html_sanitizer_strips_xss_vectors_and_escapes_cdata(): void
    {
        $sanitizer = app(HtmlSanitizerService::class);

        $malicious = '<p>Güvenli metin</p><script>alert("xss")</script><img src="x" onerror="alert(1)"><a href="javascript:evil()">Tıkla</a><a href="https://example.com">Kaynak]]></a>';
        $clean = $sanitizer->sanitizeForCdata($malicious);

        $this->assertStringContainsString('<p>Güvenli metin</p>', $clean);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('alert("xss")', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer nofollow"', $clean);
        $this->assertStringNotContainsString(']]>', $clean);
    }

    public function test_article_extractor_parses_json_ld_and_dom_paragraphs_cleanly(): void
    {
        $extractor = app(ArticleExtractorService::class);

        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta property="og:image" content="https://cdn.example.test/manset.jpg">
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "NewsArticle",
        "headline": "Yerli uydu yörünge testlerini başarıyla tamamladı",
        "description": "Yerli haberleşme uydusunun yörünge testleri planlanan takvim doğrultusunda tamamlandı.",
        "articleBody": "Yerli haberleşme uydusunun yörünge testleri planlanan takvim doğrultusunda başarıyla sona erdi.\n\nMühendislik ekibi tarafından yürütülen sinyal doğrulama aşamalarında tüm alt sistemlerin tam kapasiteyle çalıştığı ifade edildi.\n\nProje kapsamında geliştirilen yeni nesil aktarıcıların önümüzdeki ay resmen devreye alınacağı belirtildi."
    }
    </script>
</head>
<body>
    <article>
        <div class="related-news"><p>İlginizi çekebilir: Eski haber bağlantısı metni burada yer alıyor.</p></div>
    </article>
</body>
</html>
HTML;

        $extracted = $extractor->extractFromHtml($html, 'Yerli uydu yörünge testlerini başarıyla tamamladı');

        $this->assertGreaterThan(250, mb_strlen($extracted['body'], 'UTF-8'));
        $this->assertStringContainsString('sinyal doğrulama aşamalarında', $extracted['body']);
        $this->assertStringNotContainsString('İlginizi çekebilir', $extracted['body']);
        $this->assertSame('https://cdn.example.test/manset.jpg', $extracted['image_url']);
    }

    public function test_url_security_service_blocks_ssrf_and_non_http_schemes(): void
    {
        $security = app(UrlSecurityService::class);

        $this->assertFalse($security->isSafePublicUrl('http://127.0.0.1/rss.xml'));
        $this->assertFalse($security->isSafePublicUrl('http://localhost/admin'));
        $this->assertFalse($security->isSafePublicUrl('http://169.254.169.254/latest/meta-data/'));
        $this->assertFalse($security->isSafePublicUrl('http://192.168.1.10/feed'));
        $this->assertFalse($security->isSafePublicUrl('http://10.0.0.5/feed'));
        $this->assertFalse($security->isSafePublicUrl('http://[::1]/feed'));
        $this->assertFalse($security->isSafePublicUrl('file:///etc/passwd'));
        $this->assertFalse($security->isSafePublicUrl('javascript:alert(1)'));
        $this->assertFalse($security->isSafePublicUrl('https://user:pass@example.test/feed'));

        $this->assertTrue($security->isSafePublicUrl('https://portal.example.test/rss.xml'));
    }
}
