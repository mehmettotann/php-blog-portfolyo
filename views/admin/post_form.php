<?php
use App\Helpers;

$isEdit = !empty($post['id']);
$publishedVal = '';
if (!empty($post['published_at'])) {
    $publishedVal = str_replace(' ', 'T', substr((string) $post['published_at'], 0, 16));
}
?>

<form action="/admin/yazilar/kaydet" method="post" enctype="multipart/form-data" class="admin-form-layout" id="post-form">
    <?= Helpers::csrfField() ?>
    <input type="hidden" name="id" value="<?= (int) ($post['id'] ?? 0) ?>">

    <div class="admin-form-main">
        <div class="admin-card">
            <div class="form-stack">
                <div class="form-group">
                    <label for="post-title">Yazı Başlığı *</label>
                    <input type="text" id="post-title" name="title" required value="<?= Helpers::e((string) ($post['title'] ?? '')) ?>" placeholder="Örn: Paylaşımlı Hostingte Yüksek Performanslı PHP Mimarisi">
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="post-slug">URL (Slug) <span class="hint-inline">Boş bırakılırsa otomatik üretilir</span></label>
                        <input type="text" id="post-slug" name="slug" value="<?= Helpers::e((string) ($post['slug'] ?? '')) ?>" placeholder="otomatik-url-olusturulur">
                    </div>
                    <div class="form-group">
                        <label for="post-category">Kategori</label>
                        <select id="post-category" name="category_id">
                            <option value="">— Kategorisiz —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>" <?= (int) ($post['category_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>>
                                    <?= Helpers::e((string) $cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="post-excerpt">Kısa Özet (Meta açıklama ve kartlarda görünür)</label>
                    <textarea id="post-excerpt" name="excerpt" rows="3" placeholder="Yazının 1-2 cümlelik kısa özeti..."><?= Helpers::e((string) ($post['excerpt'] ?? '')) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Blok Tabanlı Zengin İçerik Düzenleyici -->
        <div class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>Yazı İçeriği (Blok Düzenleyici)</h2>
                    <p class="muted-text small-note">Paragraflar arasına dilediğiniz kadar görsel, kendi MP4/WebM videonuzu, YouTube/Vimeo videosu, kod veya alıntı ekleyip sıralayabilirsiniz.</p>
                </div>
            </div>

            <textarea name="content_blocks_json" id="content-blocks-json" hidden><?= Helpers::e((string) ($post['content_blocks'] ?? '[]')) ?></textarea>

            <div class="block-editor" data-block-editor data-target-input="content-blocks-json" data-max-image="<?= (int) $maxImageMb ?>" data-max-video="<?= (int) $maxVideoMb ?>">
                <div class="blocks-container" data-blocks-list></div>

                <div class="block-add-bar">
                    <span class="meta-label">Blok Ekle:</span>
                    <button type="button" class="btn btn-secondary btn-xs" data-add-block="paragraph">+ Paragraf</button>
                    <button type="button" class="btn btn-secondary btn-xs" data-add-block="heading">+ Alt Başlık</button>
                    <button type="button" class="btn btn-secondary btn-xs" data-add-block="image">+ Görsel</button>
                    <button type="button" class="btn btn-secondary btn-xs" data-add-block="video_local">+ Kendi Videom (MP4/WebM)</button>
                    <button type="button" class="btn btn-secondary btn-xs" data-add-block="video_external">+ YouTube / Vimeo</button>
                    <button type="button" class="btn btn-secondary btn-xs" data-add-block="list">+ Liste</button>
                    <button type="button" class="btn btn-secondary btn-xs" data-add-block="quote">+ Alıntı</button>
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
                    <label for="post-status">Durum</label>
                    <select id="post-status" name="status">
                        <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Taslak</option>
                        <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Yayında</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="post-date">Yayın Tarihi</label>
                    <input type="datetime-local" id="post-date" name="published_at" value="<?= Helpers::e($publishedVal) ?>">
                </div>

                <button type="submit" class="btn btn-primary btn-block"><?= $isEdit ? 'Değişiklikleri Kaydet' : 'Yazıyı Oluştur' ?></button>

                <?php if ($isEdit): ?>
                    <a href="/yazilar/<?= Helpers::e((string) $post['slug']) ?>" target="_blank" class="btn btn-secondary btn-block">Önizle ↗</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-card">
            <h3>Kapak Görseli</h3>
            <div class="form-stack">
                <div class="cover-preview-box" id="cover-preview-box">
                    <?php if (!empty($post['cover_image'])): ?>
                        <img src="<?= Helpers::e((string) $post['cover_image']) ?>" alt="Kapak">
                    <?php else: ?>
                        <span class="muted-text">Henüz kapak seçilmedi</span>
                    <?php endif; ?>
                </div>
                <input type="text" id="cover-image-input" name="cover_image" value="<?= Helpers::e((string) ($post['cover_image'] ?? '')) ?>" placeholder="/uploads/images/...">
                <div class="inline-btn-row">
                    <label class="btn btn-secondary btn-xs file-btn">
                        Yükle
                        <input type="file" name="cover_image_file" accept="image/*" data-ajax-cover="cover-image-input" data-ajax-preview="cover-preview-box" hidden>
                    </label>
                    <button type="button" class="btn btn-secondary btn-xs" data-pick-media="image" data-pick-target="cover-image-input" data-pick-preview="cover-preview-box">Kütüphaneden Seç</button>
                </div>
                <small class="muted-text">Maks. <?= (int) $maxImageMb ?> MB (JPG, PNG, WebP, AVIF)</small>
            </div>
        </div>
    </aside>
</form>
