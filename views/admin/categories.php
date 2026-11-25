<?php
use App\Helpers;
?>

<div class="admin-grid-2">
    <section class="admin-card">
        <div class="admin-card-head">
            <h2 id="cat-form-title">Yeni Kategori Ekle</h2>
        </div>
        <form action="/admin/kategoriler/kaydet" method="post" class="form-stack" id="category-form">
            <?= Helpers::csrfField() ?>
            <input type="hidden" name="id" id="cat-id" value="0">

            <div class="form-group">
                <label for="cat-name">Kategori Adı *</label>
                <input type="text" id="cat-name" name="name" required placeholder="Örn: Teknoloji">
            </div>

            <div class="form-group">
                <label for="cat-slug">Slug <span class="hint-inline">Boş bırakılabilir</span></label>
                <input type="text" id="cat-slug" name="slug" placeholder="teknoloji">
            </div>

            <div class="form-group">
                <label for="cat-desc">Açıklama</label>
                <textarea id="cat-desc" name="description" rows="3" placeholder="Kategori hakkında kısa açıklama..."></textarea>
            </div>

            <div class="inline-btn-row">
                <button type="submit" class="btn btn-primary btn-sm" id="cat-submit-btn">Kategori Ekle</button>
                <button type="button" class="btn btn-secondary btn-sm" id="cat-reset-btn" hidden>İptal</button>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <h2>Mevcut Kategoriler (<?= count($categories) ?>)</h2>
        </div>

        <?php if (empty($categories)): ?>
            <p class="muted-text">Henüz kategori eklenmemiş.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Ad / Slug</th>
                            <th>Yazı</th>
                            <th class="text-right">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $c): ?>
                            <tr>
                                <td>
                                    <strong><?= Helpers::e((string) $c['name']) ?></strong>
                                    <div class="row-meta">/yazilar?kategori=<?= Helpers::e((string) $c['slug']) ?></div>
                                </td>
                                <td><?= (int) $c['post_count'] ?></td>
                                <td class="text-right actions-cell">
                                    <button type="button" class="btn btn-secondary btn-xs"
                                            data-edit-category
                                            data-id="<?= (int) $c['id'] ?>"
                                            data-name="<?= Helpers::e((string) $c['name']) ?>"
                                            data-slug="<?= Helpers::e((string) $c['slug']) ?>"
                                            data-description="<?= Helpers::e((string) ($c['description'] ?? '')) ?>">
                                        Düzenle
                                    </button>
                                    <form action="/admin/kategoriler/<?= (int) $c['id'] ?>/sil" method="post" onsubmit="return confirm('Kategoriyi silmek istediğinize emin misiniz?');">
                                        <?= Helpers::csrfField() ?>
                                        <button type="submit" class="btn btn-danger-outline btn-xs">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
