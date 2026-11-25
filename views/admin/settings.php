<?php
use App\Helpers;

$skillsText = implode("\n", array_map('strval', $skills));
$interestsText = implode(', ', array_map('strval', $interests));
?>

<form action="/admin/ayarlar/kaydet" method="post" class="form-stack">
    <?= Helpers::csrfField() ?>

    <!-- Genel Site & Hero Ayarları -->
    <section class="admin-card">
        <h2>Genel Site &amp; Ana Sayfa Karşılama</h2>
        <div class="form-stack">
            <div class="form-row-2">
                <div class="form-group">
                    <label for="s-title">Site Başlığı (Ad Soyad)</label>
                    <input type="text" id="s-title" name="site_title" value="<?= Helpers::e($settings['site_title'] ?? 'Adınız Soyadınız') ?>">
                </div>
                <div class="form-group">
                    <label for="s-tagline">Kısa Unvan / Slogan</label>
                    <input type="text" id="s-tagline" name="site_tagline" value="<?= Helpers::e($settings['site_tagline'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="s-desc">SEO Meta Açıklaması</label>
                <textarea id="s-desc" name="site_description" rows="2"><?= Helpers::e($settings['site_description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="s-og-image">Varsayılan Paylaşım Görseli (WhatsApp, X, LinkedIn) <span class="hint-inline">Önerilen: 1200×630 JPG/PNG · Boşsa varsayılan site kartı kullanılır</span></label>
                <div class="form-row-2" style="align-items:center;">
                    <div class="cover-preview-box" id="og-preview-box" style="max-width:260px;">
                        <img src="<?= Helpers::e(!empty($settings['default_og_image']) ? $settings['default_og_image'] : '/assets/img/og-default.jpg') ?>" alt="Paylaşım Görseli">
                    </div>
                    <div class="form-stack">
                        <input type="text" id="s-og-image" name="default_og_image" value="<?= Helpers::e($settings['default_og_image'] ?? '') ?>" placeholder="/assets/img/og-default.jpg">
                        <div class="inline-btn-row">
                            <label class="btn btn-secondary btn-xs file-btn">
                                Görsel Yükle
                                <input type="file" accept="image/jpeg,image/png" data-ajax-cover="s-og-image" data-ajax-preview="og-preview-box" hidden>
                            </label>
                            <button type="button" class="btn btn-secondary btn-xs" data-pick-media="image" data-pick-target="s-og-image" data-pick-preview="og-preview-box">Kütüphaneden Seç</button>
                        </div>
                        <small class="muted-text">Blog yazıları ve projeler paylaşılırken kendi kapak görsellerini, kapak yoksa bu görseli kullanır.</small>
                    </div>
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label for="s-hero-badge">Karşılama Rozeti</label>
                    <input type="text" id="s-hero-badge" name="hero_badge" value="<?= Helpers::e($settings['hero_badge'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="s-location">Konum</label>
                    <input type="text" id="s-location" name="contact_location" value="<?= Helpers::e($settings['contact_location'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="s-hero-title">Ana Sayfa Başlığı</label>
                <input type="text" id="s-hero-title" name="hero_title" value="<?= Helpers::e($settings['hero_title'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="s-hero-sub">Ana Sayfa Alt Metni</label>
                <textarea id="s-hero-sub" name="hero_subtitle" rows="3"><?= Helpers::e($settings['hero_subtitle'] ?? '') ?></textarea>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label for="s-email">İletişim E-posta</label>
                    <input type="email" id="s-email" name="contact_email" value="<?= Helpers::e($settings['contact_email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="s-footer">Alt Bilgi (Footer) Metni</label>
                    <input type="text" id="s-footer" name="footer_text" value="<?= Helpers::e($settings['footer_text'] ?? '') ?>">
                </div>
            </div>
        </div>
    </section>

    <!-- Hakkımda Sayfası -->
    <section class="admin-card">
        <h2>Hakkımda Biyografisi</h2>
        <div class="form-stack">
            <div class="form-group">
                <label for="a-intro">Kısa Giriş Cümlesi</label>
                <textarea id="a-intro" name="about_intro" rows="2"><?= Helpers::e($settings['about_intro'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="a-story">Hikayem <span class="hint-inline">Paragrafları boş satırla ayırın</span></label>
                <textarea id="a-story" name="about_story" rows="8"><?= Helpers::e($settings['about_story'] ?? '') ?></textarea>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label for="a-skills">Uzmanlık Alanları <span class="hint-inline">Her satıra bir madde</span></label>
                    <textarea id="a-skills" name="about_skills_text" rows="6"><?= Helpers::e($skillsText) ?></textarea>
                </div>
                <div class="form-group">
                    <label for="a-interests">İlgi Alanları &amp; Hobiler <span class="hint-inline">Virgülle ayırın</span></label>
                    <textarea id="a-interests" name="about_interests_text" rows="6"><?= Helpers::e($interestsText) ?></textarea>
                </div>
            </div>

            <!-- Eğitim & İş Geçmişi -->
            <div class="form-group">
                <div class="admin-card-head" style="margin-bottom:0.6rem;">
                    <label style="margin:0;">Eğitim &amp; İş Geçmişi (Zaman Çizelgesi)</label>
                    <button type="button" class="btn btn-secondary btn-xs" id="add-timeline-row">+ Satır Ekle</button>
                </div>

                <div id="timeline-editor" class="form-stack">
                    <?php
                    $rows = !empty($timeline) ? $timeline : [['period' => '', 'role' => '', 'place' => '', 'detail' => '']];
                    foreach ($rows as $row):
                    ?>
                        <div class="timeline-edit-row">
                            <div class="form-row-3">
                                <input type="text" name="timeline_period[]" value="<?= Helpers::e((string) ($row['period'] ?? '')) ?>" placeholder="Dönem (Örn: 2023 – Günümüz)">
                                <input type="text" name="timeline_role[]" value="<?= Helpers::e((string) ($row['role'] ?? '')) ?>" placeholder="Rol / Bölüm">
                                <input type="text" name="timeline_place[]" value="<?= Helpers::e((string) ($row['place'] ?? '')) ?>" placeholder="Kurum / Okul">
                            </div>
                            <div class="timeline-detail-line">
                                <input type="text" name="timeline_detail[]" value="<?= Helpers::e((string) ($row['detail'] ?? '')) ?>" placeholder="Kısa açıklama...">
                                <button type="button" class="btn btn-danger-outline btn-xs" data-remove-timeline>Sil</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Sosyal Medya Bağlantıları -->
    <section class="admin-card">
        <h2>Sosyal Medya Bağlantıları</h2>
        <div class="form-row-2">
            <div class="form-group">
                <label for="soc-github">GitHub</label>
                <input type="url" id="soc-github" name="social_github" value="<?= Helpers::e((string) ($socials['github'] ?? '')) ?>" placeholder="https://github.com/...">
            </div>
            <div class="form-group">
                <label for="soc-linkedin">LinkedIn</label>
                <input type="url" id="soc-linkedin" name="social_linkedin" value="<?= Helpers::e((string) ($socials['linkedin'] ?? '')) ?>" placeholder="https://linkedin.com/in/...">
            </div>
            <div class="form-group">
                <label for="soc-x">X (Twitter)</label>
                <input type="url" id="soc-x" name="social_x" value="<?= Helpers::e((string) ($socials['x'] ?? '')) ?>" placeholder="https://x.com/...">
            </div>
            <div class="form-group">
                <label for="soc-instagram">Instagram</label>
                <input type="url" id="soc-instagram" name="social_instagram" value="<?= Helpers::e((string) ($socials['instagram'] ?? '')) ?>" placeholder="https://instagram.com/...">
            </div>
            <div class="form-group">
                <label for="soc-youtube">YouTube</label>
                <input type="url" id="soc-youtube" name="social_youtube" value="<?= Helpers::e((string) ($socials['youtube'] ?? '')) ?>" placeholder="https://youtube.com/@...">
            </div>
        </div>
    </section>

    <!-- Yönetici Şifre Değişikliği -->
    <section class="admin-card">
        <h2>Yönetici Şifresi Güncelle <span class="hint-inline">(İsteğe bağlı)</span></h2>
        <div class="form-row-2">
            <div class="form-group">
                <label for="cur-pw">Mevcut Şifre</label>
                <input type="password" id="cur-pw" name="current_password" autocomplete="current-password">
            </div>
            <div class="form-group">
                <label for="new-pw">Yeni Şifre (En az 8 karakter)</label>
                <input type="password" id="new-pw" name="new_password" autocomplete="new-password">
            </div>
        </div>
    </section>

    <div>
        <button type="submit" class="btn btn-primary">Tüm Ayarları Kaydet</button>
    </div>
</form>
