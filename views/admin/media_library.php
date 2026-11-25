<?php
use App\Helpers;
?>

<div class="admin-card">
    <div class="admin-card-head">
        <h2>Yeni Görsel veya Video Yükle</h2>
        <span class="muted-text small-note">Görsel maks. <?= (int) $maxImageMb ?> MB · Video maks. <?= (int) $maxVideoMb ?> MB</span>
    </div>

    <form action="/admin/medya/yukle" method="post" enctype="multipart/form-data" class="media-upload-inline">
        <?= Helpers::csrfField() ?>
        <select name="file_type" aria-label="Dosya türü">
            <option value="image">Görsel (JPG, PNG, WebP, GIF, AVIF)</option>
            <option value="video">Video (MP4, WebM)</option>
        </select>
        <input type="file" name="media_file" required>
        <button type="submit" class="btn btn-primary btn-sm">Yükle</button>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-head">
        <h2>Kütüphane (<?= count($files) ?> dosya)</h2>
        <div class="category-pills">
            <a href="/admin/medya" class="pill <?= $filter === 'all' ? 'is-active' : '' ?>">Tümü</a>
            <a href="/admin/medya?tur=image" class="pill <?= $filter === 'image' ? 'is-active' : '' ?>">Görseller</a>
            <a href="/admin/medya?tur=video" class="pill <?= $filter === 'video' ? 'is-active' : '' ?>">Videolar</a>
        </div>
    </div>

    <?php if (empty($files)): ?>
        <p class="muted-text">Henüz medya dosyası yüklenmemiş.</p>
    <?php else: ?>
        <div class="media-lib-grid">
            <?php foreach ($files as $f): ?>
                <div class="media-lib-card">
                    <div class="media-lib-thumb">
                        <?php if ($f['file_type'] === 'image'): ?>
                            <img src="<?= Helpers::e((string) $f['file_path']) ?>" alt="<?= Helpers::e((string) $f['original_name']) ?>" loading="lazy">
                        <?php else: ?>
                            <video src="<?= Helpers::e((string) $f['file_path']) ?>" preload="metadata" controls playsinline></video>
                        <?php endif; ?>
                    </div>
                    <div class="media-lib-info">
                        <strong title="<?= Helpers::e((string) $f['original_name']) ?>"><?= Helpers::e((string) $f['original_name']) ?></strong>
                        <div class="row-meta"><?= Helpers::formatBytes((int) $f['file_size']) ?> · <?= Helpers::e((string) $f['mime_type']) ?></div>
                        <input type="text" readonly value="<?= Helpers::e((string) $f['file_path']) ?>" class="path-copy-input" onclick="this.select();">
                        <div class="inline-btn-row">
                            <button type="button" class="btn btn-secondary btn-xs" data-copy-path="<?= Helpers::e((string) $f['file_path']) ?>">Yolu Kopyala</button>
                            <form action="/admin/medya/<?= (int) $f['id'] ?>/sil" method="post" onsubmit="return confirm('Bu dosyayı sunucudan kalıcı olarak silmek istediğinize emin misiniz?');">
                                <?= Helpers::csrfField() ?>
                                <button type="submit" class="btn btn-danger-outline btn-xs">Sil</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
