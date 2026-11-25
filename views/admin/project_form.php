<?php
use App\Helpers;

$isEdit = !empty($project['id']);
$mediaJson = json_encode($mediaItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
?>

<form action="/admin/projeler/kaydet" method="post" enctype="multipart/form-data" class="admin-form-layout" id="project-form">
    <?= Helpers::csrfField() ?>
    <input type="hidden" name="id" value="<?= (int) ($project['id'] ?? 0) ?>">

    <div class="admin-form-main">
        <!-- Temel Proje Bilgileri -->
        <div class="admin-card">
            <div class="form-stack">
                <div class="form-group">
                    <label for="pr-title">Proje Başlığı *</label>
                    <input type="text" id="pr-title" name="title" required value="<?= Helpers::e((string) ($project['title'] ?? '')) ?>" placeholder="Örn: Kişisel Medya & Blog Altyapısı">
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="pr-slug">URL (Slug)</label>
                        <input type="text" id="pr-slug" name="slug" value="<?= Helpers::e((string) ($project['slug'] ?? '')) ?>" placeholder="otomatik-olusturulur">
                    </div>
                    <div class="form-group">
                        <label for="pr-year">Yıl / Dönem</label>
                        <input type="text" id="pr-year" name="project_year" value="<?= Helpers::e((string) ($project['project_year'] ?? date('Y'))) ?>" placeholder="2026">
                    </div>
                </div>

                <div class="form-group">
                    <label for="pr-summary">Kısa Özet *</label>
                    <textarea id="pr-summary" name="summary" rows="3" required placeholder="Projenin amacını ve öne çıkan yönlerini 1-2 cümleyle özetleyin..."><?= Helpers::e((string) ($project['summary'] ?? '')) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="pr-tech">Kullanılan Teknolojiler / Etiketler <span class="hint-inline">Virgülle ayırın</span></label>
                    <input type="text" id="pr-tech" name="technologies" value="<?= Helpers::e((string) ($project['technologies'] ?? '')) ?>" placeholder="PHP 8, MySQL, LiteSpeed, Vanilla JS, HTML5 Video">
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="pr-live">Canlı Bağlantı (Live URL)</label>
                        <input type="url" id="pr-live" name="live_url" value="<?= Helpers::e((string) ($project['live_url'] ?? '')) ?>" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label for="pr-source">Kaynak Kodu (GitHub vb.)</label>
                        <input type="url" id="pr-source" name="source_url" value="<?= Helpers::e((string) ($project['source_url'] ?? '')) ?>" placeholder="https://github.com/...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Proje Medya Yöneticisi: Çoklu Görsel + Çoklu Video (Yerel & YouTube/Vimeo) -->
        <div class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>Proje Galerisi &amp; Videoları</h2>
                    <p class="muted-text small-note">Çoklu ekran görüntüsü, kendi yüklediğiniz MP4/WebM tanıtım videoları ve YouTube/Vimeo bağlantılarını bir arada ekleyip sıralayabilirsiniz.</p>
                </div>
            </div>

            <textarea name="project_media_json" id="project-media-json" hidden><?= Helpers::e($mediaJson) ?></textarea>

            <div class="project-media-manager" data-project-media-manager data-target-input="project-media-json" data-max-image="<?= (int) $maxImageMb ?>" data-max-video="<?= (int) $maxVideoMb ?>">
                <div class="pm-toolbar">
                    <label class="btn btn-secondary btn-xs file-btn">
                        + Çoklu Görsel Yükle
                        <input type="file" accept="image/*" multiple data-pm-upload-images hidden>
                    </label>
                    <button type="button" class="btn btn-secondary btn-xs" data-pm-pick-image>+ Kütüphaneden Görsel</button>
                    <label class="btn btn-secondary btn-xs file-btn">
                        + Video Yükle (MP4/WebM)
                        <input type="file" accept="video/mp4,video/webm" data-pm-upload-video hidden>
                    </label>
                    <button type="button" class="btn btn-secondary btn-xs" data-pm-pick-video>+ Sunucudan Video Seç</button>
                    <button type="button" class="btn btn-primary btn-xs" data-pm-add-external>+ YouTube / Vimeo Ekle</button>
                </div>

                <div class="upload-progress-bar" data-pm-progress hidden>
                    <div class="upload-progress-fill" data-pm-progress-fill></div>
                    <span data-pm-progress-text>Yükleniyor...</span>
                </div>

                <div class="pm-list" data-pm-list></div>
            </div>
        </div>

        <!-- Proje Detaylı Açıklama Blokları -->
        <div class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>Detaylı Proje Hikayesi (Blok Düzenleyici)</h2>
                    <p class="muted-text small-note">Projenin mimarisini, karşılaşılan zorlukları ve sonuçları detaylı olarak anlatabilirsiniz.</p>
                </div>
            </div>

            <textarea name="content_blocks_json" id="project-blocks-json" hidden><?= Helpers::e((string) ($project['description_blocks'] ?? '[]')) ?></textarea>

            <div class="block-editor" data-block-editor data-target-input="project-blocks-json" data-max-image="<?= (int) $maxImageMb ?>" data-max-video="<?= (int) $maxVideoMb ?>">
                <div class="blocks-container" data-blocks-list></div>
                <div class="block-add-bar">
                    <span class="meta-label">Blok Ekle:</span>
                    <button type="button" class="btn btn-secondary btn-xs" data-add-block="paragraph">+ Paragraf</button>
                    <button type="button" class="btn btn-secondary btn-xs" data-add-block="heading">+ Alt Başlık</button>
                    <button type="button" class="btn btn-secondary btn-xs" data-add-block="image">+ Görsel</button>
                    <button type="button" class="btn btn-secondary btn-xs" data-add-block="video_local">+ Yerel Video</button>
                    <button type="button" class="btn btn-secondary btn-xs" data-add-block="video_external">+ YouTube/Vimeo</button>
                    <button type="button" class="btn btn-secondary btn-xs" data-add-block="list">+ Liste</button>
                    <button type="button" class="btn btn-secondary btn-xs" data-add-block="code">+ Kod</button>
                </div>
            </div>
        </div>
    </div>

    <aside class="admin-form-side">
        <div class="admin-card">
            <h3>Yayın Ayarları</h3>
            <div class="form-stack">
                <div class="form-group">
                    <label for="pr-status">Durum</label>
                    <select id="pr-status" name="status">
                        <option value="published" <?= ($project['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Yayında</option>
                        <option value="draft" <?= ($project['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Taslak</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_featured" value="1" <?= !empty($project['is_featured']) ? 'checked' : '' ?>>
                        <span>Ana sayfada öne çıkar</span>
                    </label>
                </div>

                <div class="form-group">
                    <label for="pr-order">Sıralama (Küçük sayı önce görünür)</label>
                    <input type="number" id="pr-order" name="sort_order" value="<?= (int) ($project['sort_order'] ?? 0) ?>">
                </div>

                <button type="submit" class="btn btn-primary btn-block"><?= $isEdit ? 'Değişiklikleri Kaydet' : 'Projeyi Kaydet' ?></button>

                <?php if ($isEdit): ?>
                    <a href="/projeler/<?= Helpers::e((string) $project['slug']) ?>" target="_blank" class="btn btn-secondary btn-block">Projeyi Gör ↗</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-card">
            <h3>Kapak Görseli</h3>
            <div class="form-stack">
                <div class="cover-preview-box" id="pr-cover-preview">
                    <?php if (!empty($project['cover_image'])): ?>
                        <img src="<?= Helpers::e((string) $project['cover_image']) ?>" alt="Proje Kapağı">
                    <?php else: ?>
                        <span class="muted-text">Henüz kapak seçilmedi</span>
                    <?php endif; ?>
                </div>
                <input type="text" id="pr-cover-input" name="cover_image" value="<?= Helpers::e((string) ($project['cover_image'] ?? '')) ?>" placeholder="/uploads/images/...">
                <div class="inline-btn-row">
                    <label class="btn btn-secondary btn-xs file-btn">
                        Yükle
                        <input type="file" name="cover_image_file" accept="image/*" data-ajax-cover="pr-cover-input" data-ajax-preview="pr-cover-preview" hidden>
                    </label>
                    <button type="button" class="btn btn-secondary btn-xs" data-pick-media="image" data-pick-target="pr-cover-input" data-pick-preview="pr-cover-preview">Kütüphaneden Seç</button>
                </div>
            </div>
        </div>
    </aside>
</form>
