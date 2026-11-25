-- =========================================================================
-- example.com — Örnek Başlangıç Verileri (Seed Data)
-- =========================================================================

SET NAMES utf8mb4;

-- 1. Site & Hakkımda Ayarları
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_title', 'Adınız Soyadınız'),
('site_tagline', 'Yazılım, Teknoloji & Dijital Portfolyo'),
('site_description', 'Adınız Soyadınız kişisel web sitesi: yazılım geliştirme, teknoloji notları, incelemeler ve dijital projeler.'),
('hero_badge', 'example.com · Kişisel Alan'),
('hero_title', 'Merhaba, ben Adınız Soyadınız. Öğreniyor, üretiyor ve paylaşıyorum.'),
('hero_subtitle', 'Web teknolojileri, hafif yazılım mimarileri ve dijital üretim üzerine notlarımı, proje çalışmalarımı ve incelemelerimi bu kişisel alanda topluyorum.'),
('about_intro', 'Merak eden, sade ve hızlı sistemler kurmayı seven bir geliştirici ve üreticiyim.'),
('about_story', 'Teknolojiyle tanıştığım ilk günden beri karmaşık problemleri sade, anlaşılır ve hızlı çalışan çözümlere dönüştürmek en büyük tutkum oldu.\n\nBu kişisel web sitesi hem öğrendiklerimi kalıcı hale getirdiğim bir dijital defter hem de üzerinde çalıştığım projeleri, demoları ve teknik incelemeleri paylaştığım açık bir vitrin olarak tasarlandı.\n\nAğır ve hantal araçlar yerine kaynaklarını verimli kullanan, uzun ömürlü ve bakımı kolay mimariler inşa etmeye özen gösteriyorum.'),
('about_skills', '["PHP 8 & MySQL","Web Performans & LiteSpeed","Modern HTML5 / CSS3 / JS","Linux & Sunucu Yönetimi","UI/UX ve Minimalist Tasarım","Medya & Video Akışı"]'),
('about_interests', '["Yazılım Mimarisi","Açık Kaynak","Minimalizm","Fotoğraf & Video","Teknik İncelemeler","Otomasyon"]'),
('about_timeline', '[{"period":"2024 – Günümüz","role":"Bağımsız Web Geliştirici & İçerik Üreticisi","place":"example.com","detail":"Hafif web uygulamaları, performans odaklı blog/portfolyo altyapıları ve teknik içerikler üretiyorum."},{"period":"2021 – 2024","role":"Web Teknolojileri & Sistem Geliştirme","place":"Kişisel ve Kurumsal Projeler","detail":"PHP, MySQL ve modern ön yüz teknolojileriyle ölçeklenebilir ve düşük maliyetli web projeleri geliştirdim."},{"period":"2017 – 2021","role":"Üniversite & Teknik Eğitim","place":"Bilgisayar ve Yazılım Alanı","detail":"Veritabanı tasarımı, ağ temelleri ve web programlama üzerine yoğunlaştım."}]'),
('social_links', '{"github":"https://github.com/kullaniciadiniz","linkedin":"https://linkedin.com/in/kullaniciadiniz","x":"https://x.com/kullaniciadiniz","instagram":"https://instagram.com/kullaniciadiniz","youtube":"https://youtube.com/@kullaniciadiniz"}'),
('contact_email', 'iletisim@example.com'),
('contact_location', 'Türkiye'),
('footer_text', '© 2026 Adınız Soyadınız · Sade, hızlı ve özenle geliştirildi.')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- 2. Kategoriler
INSERT IGNORE INTO `categories` (`id`, `name`, `slug`, `description`, `created_at`) VALUES
(1, 'Teknoloji', 'teknoloji', 'Web mimarileri, sunucu performansı ve yazılım geliştirme üzerine teknik yazılar.', NOW()),
(2, 'İnceleme', 'inceleme', 'Araçlar, yazılımlar, medya oynatıcılar ve donanım deneyimleri.', NOW()),
(3, 'Notlar', 'notlar', 'Kısa öğrenme notları, ipuçları ve hızlı başvuru rehberleri.', NOW()),
(4, 'Günlük', 'gunluk', 'Kişisel düşünceler, üretim süreçleri ve çalışma kültürü.', NOW());

-- 3. Medya Kütüphanesi Kayıtları (Örnek görseller + gerçek MP4 demo video)
INSERT IGNORE INTO `media_files` (`file_type`, `file_path`, `original_name`, `mime_type`, `file_size`, `created_at`) VALUES
('image', '/uploads/images/sample/blog-php-litespeed.png', 'blog-php-litespeed.png', 'image/png', 27648, NOW()),
('image', '/uploads/images/sample/blog-video-medya.png', 'blog-video-medya.png', 'image/png', 30720, NOW()),
('image', '/uploads/images/sample/blog-calisma-notlari.png', 'blog-calisma-notlari.png', 'image/png', 27648, NOW()),
('image', '/uploads/images/sample/proje-blog-kapak.png', 'proje-blog-kapak.png', 'image/png', 26624, NOW()),
('image', '/uploads/images/sample/proje-galeri-1.png', 'proje-galeri-1.png', 'image/png', 29696, NOW()),
('image', '/uploads/images/sample/proje-galeri-2.png', 'proje-galeri-2.png', 'image/png', 28672, NOW()),
('image', '/uploads/images/sample/proje-not-kapak.png', 'proje-not-kapak.png', 'image/png', 30720, NOW()),
('image', '/uploads/images/sample/video-poster.png', 'video-poster.png', 'image/png', 30720, NOW()),
('video', '/uploads/videos/sample/demo-tanitim.mp4', 'demo-tanitim.mp4', 'video/mp4', 88064, NOW());

-- 4. Örnek Blog Yazıları (Görsel, Kendi MP4 Videosu ve YouTube/Vimeo Gömme Örnekleri İçerir)
INSERT IGNORE INTO `posts` (`id`, `category_id`, `title`, `slug`, `excerpt`, `content_blocks`, `content_text`, `cover_image`, `reading_time`, `status`, `published_at`, `created_at`, `updated_at`) VALUES
(
    1,
    1,
    'Paylaşımlı Hostingte (1 GB RAM) PHP 8 ve LiteSpeed ile Yüksek Performans',
    'paylasimli-hostingte-php-8-ve-litespeed-ile-yuksek-performans',
    'Ağır CMS sistemleri yerine yalın PHP 8 + PDO + LiteSpeed Cache kombinasyonu ile 1 GB RAM ve 1 Core CPU üzerinde milisaniyeler içinde açılan bir site kurma notlarım.',
    '[{"type":"paragraph","text":"Paylaşımlı hosting ortamlarında (özellikle **1024 MB RAM ve 1 Core CPU** sınırlarında) sitenin hızlı kalmasının sırrı, gereksiz bağımlılıkları ve ağır eklenti zincirlerini ortadan kaldırmaktır. Bu sitede tam olarak bu yaklaşımı benimsedim."},{"type":"heading","level":2,"text":"Neden Modern PHP 8 ve PDO?"},{"type":"paragraph","text":"PHP 8.x sürümleri OPcache ile birlikte çalıştığında çok düşük bellek tüketimiyle (istek başına yalnızca birkaç MB) yüksek eşzamanlı trafik karşılayabilir. Veritabanı tarafında doğrudan `PDO` ve **prepared statements** kullanmak hem hız hem de SQL injection güvenliği sağlar."},{"type":"image","src":"/uploads/images/sample/proje-galeri-2.png","alt":"Blok tabanlı içerik düzenleyici mimarisi","caption":"Yazıların arasına dilediğiniz yerde görsel ve video blokları yerleştirilebilir."},{"type":"heading","level":2,"text":"Kendi Sunucumuzdan HTML5 Video Akışı (Byte-Range)"},{"type":"paragraph","text":"LiteSpeed üzerinde kendi MP4/WebM videolarımızı barındırırken `.htaccess` dosyasında video dosyaları için sıkıştırmayı (`no-gzip`) kapatmak ve `Accept-Ranges: bytes` başlığını korumak gerekir. Aşağıda sunucudan doğrudan oynatılan, `preload=\\"metadata\\"` ile sayfayı yavaşlatmayan örnek bir yerel video oynatıcı yer alıyor:"},{"type":"video_local","src":"/uploads/videos/sample/demo-tanitim.mp4","poster":"/uploads/images/sample/video-poster.png","title":"Yerel HTML5 Video Örneği","caption":"Bu video doğrudan /uploads/videos/sample/demo-tanitim.mp4 yolundan oynatılmaktadır."},{"type":"code","language":"apache","code":"<FilesMatch \\"\\\\.(mp4|webm)$\\">\\n    Header set Accept-Ranges \\"bytes\\"\\n    Header set Cache-Control \\"public, max-age=2592000\\"\\n</FilesMatch>"},{"type":"heading","level":2,"text":"LiteSpeed Cache Etiketleri"},{"type":"paragraph","text":"Ziyaretçilere sunulan herkese açık sayfalar `X-LiteSpeed-Cache-Control: public` başlığı ile önbelleklenirken, yönetim paneli (`/admin`) ve iletişim formu her zaman önbellek dışında bırakılır. Panelden bir yazı güncellediğinizde `X-LiteSpeed-Purge` başlığı otomatik olarak ilgili önbelleği temizler."}]',
    'Paylaşımlı hosting ortamlarında PHP 8 ve PDO ile yüksek performans. LiteSpeed Cache ve HTML5 MP4 video streaming için byte-range ayarları.',
    '/uploads/images/sample/blog-php-litespeed.png',
    3,
    'published',
    NOW() - INTERVAL 2 DAY,
    NOW() - INTERVAL 2 DAY,
    NOW() - INTERVAL 2 DAY
),
(
    2,
    2,
    'Blog ve Portfolyolarda Video Deneyimi: Self-Hosted ve YouTube/Vimeo Birlikte',
    'blog-ve-portfolyolarda-video-deneyimi-self-hosted-ve-youtube-vimeo',
    'Kısa arayüz demolarını doğrudan sunucudan HTML5 video ile, uzun anlatımları ise YouTube veya Vimeo üzerinden lazy-load iframe ile sunmanın avantajları.',
    '[{"type":"paragraph","text":"Bir projeyi veya teknik incelemeyi anlatırken bazen 10 saniyelik bir ekran kaydı bin kelimeden daha etkili olur. Ancak her video aynı barındırma stratejisini gerektirmez."},{"type":"list","style":"unordered","items":["**Kendi Videolarım (MP4/WebM):** Reklamsız, hızlı başlayan kısa arayüz demoları ve ürün tanıtımları için idealdir.","**YouTube / Vimeo:** Uzun eğitim videoları, yüksek çözünürlüklü kayıtlar ve bant genişliğini korumak istenen içerikler için mükemmeldir."]},{"type":"heading","level":2,"text":"Harici Video Bağlantılarının Otomatik Ayrıştırılması"},{"type":"paragraph","text":"Yönetim panelinde yalnızca bir YouTube veya Vimeo bağlantısı yapıştırmak yeterlidir. Sistem video ID bilgisini otomatik ayrıştırır ve responsive 16:9 oranlı, `loading=\\"lazy\\"` destekli gizlilik dostu bir oynatıcıya dönüştürür:"},{"type":"video_external","url":"https://www.youtube.com/watch?v=aqz-KE-bpKQ","provider":"youtube","video_id":"aqz-KE-bpKQ","title":"Harici YouTube Video Örneği","caption":"Responsive 16:9 lazy-load YouTube oynatıcı (youtube-nocookie.com)."},{"type":"quote","text":"İyi bir medya deneyimi, kullanıcı videoyu oynatana kadar sayfa açılış hızından hiçbir şey eksiltmeyen deneyimdir.","cite":"Web Performans Notları"}]',
    'Blog ve portfolyolarda self-hosted MP4/WebM video ve YouTube Vimeo lazy-load iframe kullanım rehberi.',
    '/uploads/images/sample/blog-video-medya.png',
    2,
    'published',
    NOW() - INTERVAL 5 DAY,
    NOW() - INTERVAL 5 DAY,
    NOW() - INTERVAL 5 DAY
),
(
    3,
    4,
    'Sade Üretim: Dijital Bahçemi Neden Sıfırdan Kurdum?',
    'sade-uretim-dijital-bahcemi-neden-sifirdan-kurdum',
    'Sosyal medya akışlarında kaybolmayan, tamamen kontrolümde olan bir kişisel web sitesi ve dijital arşiv oluşturma motivasyonum.',
    '[{"type":"paragraph","text":"Sosyal medya platformları hızlı iletişim için harika olsa da ürettiğimiz içerikler birkaç gün içinde akışın derinliklerinde kayboluyor. Kendi alan adımız (`example.com`) altında düzenli bir bilgi arşivi ve portfolyo oluşturmak uzun vadede çok daha kalıcı."},{"type":"heading","level":2,"text":"Temel Tasarım İlkelerim"},{"type":"list","style":"ordered","items":["Okuması yormayan tipografi ve açık/koyu tema desteği.","Mobilde ve masaüstünde kusursuz responsive deneyim.","İçerik eklerken teknik sürtünme yaratmayan sade bir yönetim paneli."]},{"type":"paragraph","text":"Bu sitedeki tüm yazılar, projeler ve biyografi bilgileri doğrudan yönetim panelinden dakikalar içinde güncellenebiliyor. Yeni fikirler ve çalışmalarla burayı canlı tutmaya devam edeceğim."}]',
    'Sade üretim, dijital bahçe, kişisel web sitesi ve minimalist tasarım ilkeleri.',
    '/uploads/images/sample/blog-calisma-notlari.png',
    2,
    'published',
    NOW() - INTERVAL 10 DAY,
    NOW() - INTERVAL 10 DAY,
    NOW() - INTERVAL 10 DAY
);

-- 5. Örnek Projeler
INSERT IGNORE INTO `projects` (`id`, `title`, `slug`, `summary`, `description_blocks`, `cover_image`, `technologies`, `live_url`, `source_url`, `project_year`, `status`, `is_featured`, `sort_order`, `created_at`, `updated_at`) VALUES
(
    1,
    'example.com — Hibrit Blog & Dijital Portfolyo',
    'hibrit-blog-ve-portfolyo-platformu',
    'Paylaşımlı cPanel + LiteSpeed sunucular için özel olarak tasarlanmış, blok tabanlı içerik düzenleyici ve çoklu görsel/video destekli ultra hafif PHP 8 + MySQL web platformu.',
    '[{"type":"heading","level":2,"text":"Projenin Amacı"},{"type":"paragraph","text":"Tek bir hafif sistem içinde hem kişisel tanıtım, hem zengin içerikli blog hem de galeri ve video destekli dijital portfolyo ihtiyacını karşılamak amacıyla geliştirildi."},{"type":"heading","level":2,"text":"Öne Çıkan Teknik Özellikler"},{"type":"list","style":"unordered","items":["Harici ağır framework bağımlılığı olmadan saf PHP 8.x + PDO mimarisi.","Hem kendi MP4/WebM videolarını hem de YouTube/Vimeo bağlantılarını destekleyen birleşik medya yapısı.","LiteSpeed Cache uyumlu başlıklar ve byte-range video akışı dostu `.htaccess` kuralları.","CSRF, XSS, SQL injection ve dosya MIME/magic-byte doğrulama korumaları."]}]',
    '/uploads/images/sample/proje-blog-kapak.png',
    'PHP 8, MySQL, LiteSpeed, Vanilla JS, CSS3, HTML5 Video',
    'https://example.com',
    'https://github.com/kullaniciadiniz',
    '2026',
    'published',
    1,
    1,
    NOW(),
    NOW()
),
(
    2,
    'Odak Not Defteri — Minimalist Çalışma Aracı',
    'odak-not-defteri-minimalist-calisma-araci',
    'Dikkat dağıtıcı öğelerden arındırılmış, klavye kısayollarıyla hızlı arama ve etiketleme sunan kişisel not alma deneyimi.',
    '[{"type":"paragraph","text":"Günlük teknik notları ve yapılacak listelerini hızla yakalamak için geliştirdiğim sade bir arayüz çalışması."},{"type":"list","style":"unordered","items":["Anında kategori ve etiket filtreleme.","Koyu/açık tema geçişi ve yüksek kontrastlı okuma modu.","Düşük bellek tüketimi ile hızlı açılış."]}]',
    '/uploads/images/sample/proje-not-kapak.png',
    'JavaScript, HTML5, CSS3, UI/UX',
    'https://example.com/projeler',
    'https://github.com/kullaniciadiniz',
    '2025',
    'published',
    1,
    2,
    NOW(),
    NOW()
);

-- 6. Proje Çoklu Medya Öğeleri (Galeri Görselleri + Yerel MP4 + Harici YouTube)
INSERT IGNORE INTO `project_media` (`id`, `project_id`, `media_type`, `file_path`, `poster_path`, `external_url`, `provider`, `video_id`, `title`, `caption`, `sort_order`, `created_at`) VALUES
(1, 1, 'video_local', '/uploads/videos/sample/demo-tanitim.mp4', '/uploads/images/sample/video-poster.png', NULL, NULL, NULL, 'Yerel MP4 Demo Videosu', 'Doğrudan sunucudan HTML5 oynatıcı ile akıtılan tanıtım videosu.', 1, NOW()),
(2, 1, 'video_external', NULL, NULL, 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'youtube', 'aqz-KE-bpKQ', 'Harici YouTube Tanıtım Örneği', 'YouTube bağlantısından otomatik ayrıştırılan responsive 16:9 oynatıcı.', 2, NOW()),
(3, 1, 'image', '/uploads/images/sample/proje-galeri-1.png', NULL, NULL, NULL, NULL, 'Açık ve Koyu Tema', 'Sistem tercihine uyumlu minimalist açık/koyu tema görünümü.', 3, NOW()),
(4, 1, 'image', '/uploads/images/sample/proje-galeri-2.png', NULL, NULL, NULL, NULL, 'Blok İçerik Düzenleyici', 'Paragraflar arasına görsel, yerel video ve YouTube/Vimeo ekleme ekranı.', 4, NOW()),
(5, 1, 'image', '/uploads/images/sample/blog-php-litespeed.png', NULL, NULL, NULL, NULL, 'Performans Odaklı Yapı', 'LiteSpeed Cache ve düşük bellek tüketimli mimari.', 5, NOW()),
(6, 2, 'image', '/uploads/images/sample/proje-galeri-1.png', NULL, NULL, NULL, NULL, 'Minimalist Arayüz', 'Odak odaklı temiz tipografi.', 1, NOW()),
(7, 2, 'image', '/uploads/images/sample/blog-calisma-notlari.png', NULL, NULL, NULL, NULL, 'Not Görünümü', 'Hızlı etiketleme ve arşivleme ekranı.', 2, NOW());

-- 7. Örnek İletişim Mesajı (Yönetim panelinde test için)
INSERT IGNORE INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `ip_address`, `is_read`, `created_at`) VALUES
(1, 'Örnek Ziyaretçi', 'ornek@eposta.com', 'Yeni web siteniz hayırlı olsun!', 'Merhaba,\n\nexample.com yeni tasarımı çok sade ve hızlı olmuş. Özellikle yazı ve projelerdeki video oynatıcı desteği harika görünüyor.\n\nBaşarılar dilerim!', '127.0.0.1', 0, NOW());
