<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="public/images/logo-dark.png">
    <source media="(prefers-color-scheme: light)" srcset="public/images/logo-light.png">
    <img alt="Gece Haber Logo" src="public/images/logo-light.png" width="320">
  </picture>
</p>

<h1 align="center">Gece Haber — Otonom Haber Portalı</h1>

<p align="center">
  <strong>PHP 8.5 ve Laravel 12 mimarisiyle geliştirilmiş; tam metin haber kazıma, yerel Türkçe özgünleştirme motoru, çok katmanlı güvenlik ve rol tabanlı yönetim paneline sahip modern haber platformu.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.5%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.5+">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/Tailwind_CSS-4.0-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4.0">
  <img src="https://img.shields.io/badge/PHPStan-Level_5_(0_Errors)-2563EB?style=flat-square" alt="PHPStan Level 5">
  <img src="https://img.shields.io/badge/Tests-35_Passed_(230_Assertions)-16A34A?style=flat-square" alt="PHPUnit Tests">
</p>

---

## Öne Çıkan Özellikler

- **Otonom RSS & Tam Metin Haber Motoru:**
  - Ulusal ve sektörel RSS/Atom kaynaklarından zamanlanmış (`15` dakikada bir) veya yönetim panelinden tek tıkla asenkron (`FetchNewsJob`) haber çekimi.
  - Kısa RSS özetleriyle yetinmeyip kaynak haber sayfasından Schema.org `NewsArticle` JSON-LD ve PHP 8.5 yerleşik HTML5 ayrıştırıcısı (`\Dom\HTMLDocument`) ile reklam, menü ve telif gürültüsünden arındırılmış tam haber metnini çıkarma (`ArticleExtractorService`).
- **%100 Yerel ve Ücretsiz Türkçe Özgünleştirme (`TurkishArticleRewriterService`):**
  - Harici ücretli AI API anahtarı gerektirmeden; doğrudan alıntıları, sayısal verileri, tarihleri ve özel isimleri koruma kalkanına alarak haber metnini Türkçe gazetecilik kurallarına uygun biçimde yeniden yapılandırır, spot paragrafı ve konu duyarlı `<h2>` alt başlıkları oluşturur.
  - `SHA-256` içerik özeti (`content_hash`) ve kaynak URL kontrolü ile mükerrer haber kaydını engeller.
- **Modern Ön Yüz & Karanlık Mod (Dark Mode):**
  - Tailwind CSS 4.0 ile mobil öncelikli (mobile-first) tasarım, anlık Açık/Koyu tema geçişi, son dakika bandı, canlı piyasa verileri (`BIST 100`, `Dolar`, `Euro`, `Gram Altın`), manşet vitrini, editörün seçtikleri ve kategori blokları.
- **SEO & İçerik Sendikasyonu:**
  - Standart XML Sitemap (`/sitemap.xml`), son 48 saati kapsayan Google News Sitemap (`/sitemap-news.xml`), RSS 2.0 beslemesi (`/feed`) ve haber detay sayfalarında otomatik `NewsArticle` JSON-LD yapısal verisi.
- **Rol Tabanlı Yönetim Paneli (`/admin`):**
  - `Spatie/Laravel-Permission` altyapısıyla **Super Admin**, **Admin**, **Editor** ve **User** yetki ayrımı.
  - Haber, Kategori, RSS Kaynağı, Etiket ve Kullanıcı yönetimi; anlık otomatik tamamlamalı arama, taslak önizleme ve önbellek yönetimi.
- **Kurumsal Güvenlik & Sürpriz Maliyet Koruması:**
  - **SSRF & Yönlendirme Döngüsü Kalkanı (`UrlSecurityService`):** PHP 8.5 RFC 3986 `Uri` doğrulaması, özel/yerel IP ve bulut metadata (`169.254.169.254`) engeli, `301/302` yönlendirme zincirinde her adımda yeniden doğrulama ve öz-alan adı (`APP_URL`) döngü engeli.
  - **Devre Kesici (Circuit Breaker) & Tekil Kuyruk:** Art arda `10` kez hata veren bozuk RSS kaynaklarını otomatik pasife alma; `ShouldBeUnique` ve `WithoutOverlapping` ile çakışmasız arka plan işleri.
  - **Çift Anahtarlı Rate Limit & Günlük Sert Kota (`EnforceDailyQuota`):** Hem kullanıcı kimliği hem IP adresi bazında dakikalık hız sınırı ve aşılması imkânsız günlük işlem kotaları (`HTTP 429`).
  - **Çift Katmanlı XSS Temizliği (`HtmlSanitizerService`):** `symfony/html-sanitizer` ile hem veritabanına yazarken hem de Blade/RSS CDATA çıktısında güvenli HTML arındırması.

---

## Teknoloji Yığını

| Katman | Teknoloji |
| :--- | :--- |
| **Dil & Çalışma Ortamı** | PHP `8.5+` (`strict_types=1`, `\Dom\HTMLDocument`, `Uri\Rfc3986\Uri`, Backed Enums) |
| **Backend Çatısı** | Laravel `12.x`, Laravel Sanctum `4.x`, Database Queue & Cache |
| **Yetkilendirme & Güvenlik** | `spatie/laravel-permission`, `symfony/html-sanitizer`, FormRequest & Policy katmanı |
| **Frontend** | Blade, Tailwind CSS `4.0`, Vite `6.x`, AdminLTE 3 (Yönetim Paneli) |
| **Veritabanı** | MySQL `8.0+` / MariaDB `10.4+` (Test ortamında bellek içi SQLite desteği) |
| **Kalite Güvence** | Larastan / PHPStan (Seviye 5), PHPUnit `11.x` |

---

## Kurulum (XAMPP / Yerel Geliştirme)

### Ön Gereksinimler
- **PHP 8.5+** (`pdo_mysql`, `pdo_sqlite`, `mbstring`, `openssl`, `curl`, `fileinfo`, `gd`, `zip`, `intl`, `bcmath`, `exif` eklentileri aktif)
- **Composer 2.8+** ve **Node.js 20+ / NPM**
- **MySQL** veya **MariaDB** (XAMPP üzerinden çalıştırılabilir)

### Adım Adım Kurulum

1. **Depoyu klonlayın ve dizine geçin:**
   ```bash
   git clone https://github.com/kutay812/PHP-Laravel-HaberPortal.git
   cd PHP-Laravel-HaberPortal
   ```

2. **PHP ve Frontend bağımlılıklarını yükleyin:**
   ```bash
   composer install
   npm install
   npm run build
   ```

3. **Ortam dosyasını oluşturun ve uygulama anahtarını üretin:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   `.env` dosyasında veritabanı bilgilerinizi (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) ve isteğe bağlı başlangıç yönetici parolasını (`DEFAULT_ADMIN_PASSWORD`) yapılandırın. *(Parola boş bırakılırsa kurulum sırasında tek seferlik güçlü bir rastgele parola üretilip terminale yazdırılır.)*

4. **Depolama bağlantısını ve veritabanı tablolarını oluşturun:**
   ```bash
   php artisan storage:link
   php artisan migrate --seed
   ```

5. **Uygulamayı başlatın:**
   ```bash
   php artisan serve
   ```
   - **Ön Yüz:** `http://127.0.0.1:8000`
   - **Yönetim Paneli:** `http://127.0.0.1:8000/admin`

---

## Faydalı Artisan ve Geliştirici Komutları

```bash
# Tüm aktif RSS kaynaklarından tam metin haber çek ve Türkçe özgünleştir
php artisan news:fetch --force

# Mevcut kısa özetli haberleri kaynak URL'den eşzamanlı (Http::pool) zenginleştir
php artisan news:enrich

# Zamanlanmış görevleri (15 dakikada bir otomatik haber çekimi) çalıştır
php artisan schedule:work

# Veritabanı kuyruk işçisini çalıştır (e-posta ve arka plan işleri için)
php artisan queue:work

# Statik kod analizi (Larastan Seviye 5)
vendor/bin/phpstan analyse --memory-limit=512M

# Otomatik birim ve özellik testlerini çalıştır
php artisan test
```

---

## Ortam Değişkenleri ve Günlük Sert Kotalar

`config/security.php` üzerinden yönetilen hız sınırları ve günlük sert kotalar `.env` dosyasından özelleştirilebilir:

| Değişken | Varsayılan | Açıklama |
| :--- | :---: | :--- |
| `MARKET_DATA_API_URL` | `https://finans.truncgil.com/today.json` | Canlı döviz, altın ve BIST 100 veri kaynağı |
| `DAILY_RSS_FETCH_QUOTA` | `100` | Sistem genelinde günlük maksimum manuel RSS çekim sayısı |
| `DAILY_USER_RSS_FETCH_QUOTA` | `24` | Kullanıcı/IP başına günlük maksimum manuel RSS çekim sayısı |
| `DAILY_NEWS_WRITE_QUOTA` | `100` | Kullanıcı/IP başına günlük haber oluşturma/güncelleme kotası |
| `DAILY_COMMENT_QUOTA` | `30` | Kullanıcı/IP başına günlük yorum gönderme kotası |
| `DAILY_PROFILE_WRITE_QUOTA` | `25` | Kullanıcı/IP başına günlük profil güncelleme kotası |
| `DAILY_SEARCH_QUOTA` | `300` | IP/Kullanıcı başına günlük arama sorgusu kotası |

---

## Proje Dizin Yapısı

```text
├── app/
│   ├── Console/Commands/     # news:fetch ve news:enrich komutları
│   ├── Enums/                # NewsStatus ve RoleName tip güvenli Enum sınıfları
│   ├── Http/
│   │   ├── Controllers/      # Ön yüz ve Admin (Dashboard, News, Source, Tag, User) denetleyicileri
│   │   ├── Middleware/       # EnforceDailyQuota ve PreventBrowserBackCache katmanları
│   │   └── Requests/         # Tüm HTTP girişlerini doğrulayan FormRequest sınıfları
│   ├── Jobs/                 # FetchNewsJob (ShouldBeUnique) arka plan kuyruk işi
│   ├── Mail/                 # AdminPasswordResetMail e-posta sınıfı
│   ├── Models/               # News, Category, NewsSource, Tag, Comment, Image, User, Setting
│   └── Services/             # RssFetcher, ArticleExtractor, TurkishArticleRewriter, HtmlSanitizer, UrlSecurity
├── config/                   # Uygulama, önbellek ve güvenlik/kota (security.php) ayarları
├── database/                 # Migrasyonlar, Factory tanımları ve Seeder sınıfları
├── public/                   # Ön denetleyici (index.php), derlenmiş Vite varlıkları ve marka logoları
├── resources/                # Blade şablonları, Tailwind CSS 4.0 ve JS kaynakları
├── routes/                   # web.php, api.php ve console.php (zamanlayıcı) tanımları
└── tests/                    # Unit ve Feature test paketleri (PHPUnit 11)
```

---

## Güvenlik Mimarisi Özeti

- **Dizin Koruma Zırhı:** Kök `.htaccess` ve `public/.htaccess` kurallarıyla `.env`, `.git`, `storage/logs`, `storage/framework` ve yapılandırma dosyalarına doğrudan HTTP erişimi `403 Forbidden` ile engellenmiştir.
- **Şifreli Oturum & Doğrulama:** `SESSION_ENCRYPT=true`, CSRF koruması, oturum sabitleme (session fixation) karşıtı `session()->regenerate()` ve tüm rotalarda `FormRequest` doğrulaması aktiftir.
- **Veritabanı Güvenliği:** Tüm sorgular Eloquent ORM / Prepared Statements üzerinden çalışır; `User` modelinde parola özetleri ve oturum jetonları serileştirmede gizlenmiştir (`$hidden`).

---

## Lisans

Bu proje açık kaynaklı olup [MIT Lisansı](https://opensource.org/licenses/MIT) kapsamında sunulmaktadır.
