<?php
use App\Helpers;
?>

<div class="admin-card">
    <div class="admin-card-head">
        <h2>Tüm Blog Yazıları (<?= count($posts) ?>)</h2>
        <a href="/admin/yazilar/yeni" class="btn btn-primary btn-sm">+ Yeni Yazı Ekle</a>
    </div>

    <?php if (empty($posts)): ?>
        <p class="muted-text">Henüz blog yazısı oluşturulmamış.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Başlık</th>
                        <th>Kategori</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th class="text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $p): ?>
                        <tr>
                            <td>
                                <a href="/admin/yazilar/<?= (int) $p['id'] ?>/duzenle" class="row-title"><?= Helpers::e((string) $p['title']) ?></a>
                                <div class="row-meta">/yazilar/<?= Helpers::e((string) $p['slug']) ?> · <?= (int) $p['reading_time'] ?> dk</div>
                            </td>
                            <td><?= Helpers::e((string) ($p['category_name'] ?? '—')) ?></td>
                            <td>
                                <span class="status-pill status-<?= Helpers::e((string) $p['status']) ?>">
                                    <?= $p['status'] === 'published' ? 'Yayında' : 'Taslak' ?>
                                </span>
                            </td>
                            <td><?= Helpers::formatDateTr((string) ($p['published_at'] ?: $p['updated_at'])) ?></td>
                            <td class="text-right actions-cell">
                                <a href="/yazilar/<?= Helpers::e((string) $p['slug']) ?>" target="_blank" class="btn btn-secondary btn-xs">Gör</a>
                                <a href="/admin/yazilar/<?= (int) $p['id'] ?>/duzenle" class="btn btn-secondary btn-xs">Düzenle</a>
                                <form action="/admin/yazilar/<?= (int) $p['id'] ?>/sil" method="post" onsubmit="return confirm('Bu yazıyı kalıcı olarak silmek istediğinize emin misiniz?');">
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
</div>
