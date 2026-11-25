# PHP 8 + MySQL Kişisel Tanıtım, Blog & Dijital Portfolyo

Paylaşımlı hosting (cPanel / Plesk + LiteSpeed, 1 GB RAM / 1 Core CPU) ve lokal geliştirme ortamları için sıfır harici bağımlılıkla (Composer / Node.js gerektirmeden) geliştirilmiş; hızlı, hafif, modern ve minimalist hibrit **kişisel tanıtım + blog + dijital portfolyo** platformu.

---

## Özellikler

- **Sayfalar:**
  - **Ana Sayfa:** Kişisel karşılama alanı, ilgi alanları/uzmanlık özeti, son yayınlanan yazılar ve öne çıkan projeler.
  - **Hakkımda:** Biyografi, uzmanlık alanları, eğitim/deneyim zaman çizelgesi ve ilgi alanları (tamamı yönetim panelinden düzenlenebilir).
  - **Blog / Yazılar:** Kategori filtreleme, arama, sayfalama, okuma süresi, kapak görseli ve blok tabanlı zengin içerik.
  - **Projeler / Çalışmalar:** Teknoloji etiketlerine göre filtreleme, çoklu görsel galerisi (Lightbox) ve çoklu video vitrini.
  - **İletişim:** Sosyal medya bağlantıları ve veritabanına kaydedilen çok katmanlı spam korumalı form (Honeypot + süre kontrolü + matematik doğrulama + CSRF + IP hız sınırı).
- **Çift Kaynaklı Video & Medya Desteği (Blog + Projeler):**
  - **Kendi Videolarım (Self-Hosted):** MP4/WebM yükleme veya sunucudaki videoyu seçme, poster görseli, `preload="metadata"` ve HTTP 206 Byte-Range destekli responsive HTML5 oynatıcı.
  - **Harici Videolar (YouTube / Vimeo):** Sadece video URL'sini yapıştırın; sistem video ID'sini otomatik ayrıştırıp responsive 16:9 `loading="lazy"` iframe olarak yerleştirir.
- **Yönetim Paneli (`/admin`):**
  - **Zengin Metin & Medya Editörü (WYSIWYG):** Paragraf, H2/H3 başlık, kalın/italik/altı çizili, liste, alıntı, kod bloğu, link, HTML kaynak görünümü; editör içinden imlecin olduğu yere doğrudan görsel yükleme (Ctrl+V yapıştırma & sürükle-bırak destekli), kütüphaneden seçme, yerel MP4/WebM video yükleme ve YouTube/Vimeo gömme.
  - Sunucu tarafı `DOMDocument` tabanlı sıkı HTML temizleme (XSS koruması).
  - Proje çoklu görsel & çoklu video yöneticisi (sıralama + ilerleme çubuklu AJAX yükleyici).
  - Medya kütüphanesi, kategori yönetimi, site/hakkımda ayarları, varsayılan sosyal paylaşım görseli ve gelen mesaj yönetimi.
- **SEO & Sosyal Medya Paylaşımı:**
  - Temiz Türkçe URL'ler (`/yazilar/{slug}`, `/projeler/{slug}`), dinamik canonical URL'ler, `/sitemap.xml`, `/rss.xml`, `/robots.txt`.
  - Schema.org JSON-LD (`WebSite`, `Person`, `BlogPosting`, `CreativeWork`, `BreadcrumbList`).
  - WhatsApp, Telegram, X ve LinkedIn için otomatik kapak görselli Open Graph & Twitter Card önizlemeleri.
- **Güvenlik & Performans:**
  - PDO Prepared Statements, XSS koruması, CSRF doğrulama, giriş brute-force koruması, `finfo` magic-byte dosya doğrulaması, `/uploads/` içinde PHP çalıştırma engeli.
  - Açık/koyu tema (`light/dark mode`), mobil uyumlu tasarım, LiteSpeed Cache başlıkları ve otomatik önbellek temizleme (`X-LiteSpeed-Purge`).

---

## Gereksinimler

- **PHP 8.1+** (`pdo_mysql`, `fileinfo`, `mbstring`, `gd`, `opcache`)
- **MySQL 5.5+ / 8.0+** veya **MariaDB 10.x+**

---

## Hızlı Kurulum (Lokal Ortam)

### 1. Projeyi İndirin ve `.env` Dosyasını Oluşturun

```bash
git clone https://github.com/kullaniciadiniz/proje-adi.git
cd proje-adi
cp .env.example .env
```

`.env` dosyasını açıp kendi MySQL bilgilerinizi (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) ve ilk yönetici şifresini (`ADMIN_DEFAULT_PASSWORD`) düzenleyin.

### 2. MySQL Veritabanı Oluşturun

```sql
CREATE DATABASE blog_portfolio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'db_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON blog_portfolio.* TO 'db_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Tabloları ve Örnek Verileri Kurun

```bash
php database/install.php
```
*(Örnek veriler olmadan kurmak için `php database/install.php --no-seed` kullanabilirsiniz.)*

### 4. Lokal Sunucuyu Başlatın

```bash
./scripts/start-local.sh
```
*(Veya manuel: `php -d upload_max_filesize=100M -d post_max_size=110M -S 127.0.0.1:8000 router.php`)*

- **Site:** `http://127.0.0.1:8000`
- **Yönetim Paneli:** `http://127.0.0.1:8000/admin/giris`

---

## Paylaşımlı Hosting'e (cPanel / Plesk + LiteSpeed) Taşıma

1. Veritabanını oluşturup `database/schema.sql` (ve isteğe bağlı `database/seed.sql`) dosyasını içe aktarın veya `php database/install.php` çalıştırın.
2. Proje dosyalarını (`.htaccess` ve `uploads/.htaccess` dahil; `router.php` ve `scripts/` hariç) `public_html/` veya `httpdocs/` dizinine yükleyin.
3. Sunucudaki `.env` dosyasında canlı değerleri ayarlayın:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://example.com
   DB_HOST=localhost
   LITESPEED_CACHE_ENABLED=true
   ```
4. Hosting panelinden PHP **8.2 / 8.3 / 8.4** seçin ve video yüklemeleri için `upload_max_filesize` / `post_max_size` değerlerini ihtiyacınıza göre artırın.
5. SSL aktif olduktan sonra `.htaccess` içindeki HTTPS yönlendirmesini açın (veya panelden 301 HTTPS yönlendirmesini etkinleştirin).
6. İlk girişten sonra **Yönetim Paneli → Site & Hakkımda** bölümünden site başlığı, biyografi, sosyal medya linkleri ve yönetici şifrenizi güncelleyin.
