<?php
use App\Helpers;

$socialMeta = [
    'github' => ['label' => 'GitHub', 'desc' => 'Açık kaynak projeler ve kodlarım'],
    'linkedin' => ['label' => 'LinkedIn', 'desc' => 'Profesyonel profil ve bağlantılar'],
    'x' => ['label' => 'X (Twitter)', 'desc' => 'Kısa notlar ve teknoloji paylaşımları'],
    'instagram' => ['label' => 'Instagram', 'desc' => 'Günlük kareler ve fotoğrafçılık'],
    'youtube' => ['label' => 'YouTube', 'desc' => 'Video anlatımlar ve demolar'],
];
?>

<section class="page-header">
    <div class="container">
        <span class="section-kicker">İletişim</span>
        <h1>Birlikte Konuşalım</h1>
        <p class="lead">Projeler, teknik sorular, iş birliği fikirleri veya sadece selam vermek için aşağıdaki formu doldurabilir ya da sosyal medya üzerinden ulaşabilirsiniz.</p>
    </div>
</section>

<section class="section">
    <div class="container contact-layout">
        <div class="contact-form-card">
            <h2>Mesaj Gönder</h2>
            <form action="/iletisim" method="post" class="form-stack" novalidate>
                <?= Helpers::csrfField() ?>

                <!-- Spam koruması: Honeypot (gerçek kullanıcılar görmez) -->
                <div class="hp-field" aria-hidden="true">
                    <label for="website">Web Sitesi</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="c-name">Ad Soyad *</label>
                        <input type="text" id="c-name" name="name" required maxlength="120"
                               value="<?= Helpers::e((string) ($old['name'] ?? '')) ?>" placeholder="Adınız Soyadınız">
                    </div>

                    <div class="form-group">
                        <label for="c-email">E-posta *</label>
                        <input type="email" id="c-email" name="email" required maxlength="180"
                               value="<?= Helpers::e((string) ($old['email'] ?? '')) ?>" placeholder="ornek@alanadi.com">
                    </div>
                </div>

                <div class="form-group">
                    <label for="c-subject">Konu</label>
                    <input type="text" id="c-subject" name="subject" maxlength="180"
                           value="<?= Helpers::e((string) ($old['subject'] ?? '')) ?>" placeholder="Kısaca mesajınızın konusu">
                </div>

                <div class="form-group">
                    <label for="c-message">Mesajınız *</label>
                    <textarea id="c-message" name="message" rows="6" required maxlength="5000"
                              placeholder="Mesajınızı buraya yazın..."><?= Helpers::e((string) ($old['message'] ?? '')) ?></textarea>
                </div>

                <div class="form-group captcha-box">
                    <label for="c-captcha">Spam Koruması: <strong><?= Helpers::e($captchaQuestion) ?></strong> *</label>
                    <input type="number" id="c-captcha" name="captcha" required inputmode="numeric" placeholder="Sonucu yazın">
                </div>

                <button type="submit" class="btn btn-primary">Mesajı Gönder</button>
            </form>
        </div>

        <aside class="contact-sidebar">
            <div class="info-card">
                <h3>Doğrudan Ulaşım</h3>
                <?php if (!empty($settings['contact_email'])): ?>
                    <div class="contact-detail-item">
                        <span class="meta-label">E-posta</span>
                        <a href="mailto:<?= Helpers::e((string) $settings['contact_email']) ?>"><?= Helpers::e((string) $settings['contact_email']) ?></a>
                    </div>
                <?php endif; ?>
                <?php if (!empty($settings['contact_location'])): ?>
                    <div class="contact-detail-item">
                        <span class="meta-label">Konum</span>
                        <span><?= Helpers::e((string) $settings['contact_location']) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($socials)): ?>
                <div class="info-card">
                    <h3>Sosyal Medya</h3>
                    <div class="social-list">
                        <?php foreach ($socialMeta as $key => $meta): ?>
                            <?php if (empty($socials[$key])) continue; ?>
                            <a href="<?= Helpers::safeHref((string) $socials[$key]) ?>" target="_blank" rel="noopener noreferrer" class="social-item">
                                <div>
                                    <strong><?= $meta['label'] ?></strong>
                                    <span><?= $meta['desc'] ?></span>
                                </div>
                                <span aria-hidden="true">↗</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</section>
