<?php
use App\Helpers;
?>

<div class="admin-card">
    <div class="admin-card-head">
        <h2>Projeler &amp; Çalışmalar (<?= count($projects) ?>)</h2>
        <a href="/admin/projeler/yeni" class="btn btn-primary btn-sm">+ Yeni Proje Ekle</a>
    </div>

    <?php if (empty($projects)): ?>
        <p class="muted-text">Henüz proje eklenmemiş.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Proje</th>
                        <th>Medya</th>
                        <th>Durum</th>
                        <th class="text-right">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $pr): ?>
                        <tr>
                            <td>
                                <a href="/admin/projeler/<?= (int) $pr['id'] ?>/duzenle" class="row-title">
                                    <?= Helpers::e((string) $pr['title']) ?>
                                    <?php if (!empty($pr['is_featured'])): ?>
                                        <span class="badge-featured">Öne Çıkan</span>
                                    <?php endif; ?>
                                </a>
                                <div class="row-meta">/projeler/<?= Helpers::e((string) $pr['slug']) ?> · <?= Helpers::e((string) ($pr['project_year'] ?? '')) ?></div>
                            </td>
                            <td>
                                <span class="row-meta"><?= (int) $pr['image_count'] ?> görsel · <?= (int) $pr['video_count'] ?> video</span>
                            </td>
                            <td>
                                <span class="status-pill status-<?= Helpers::e((string) $pr['status']) ?>">
                                    <?= $pr['status'] === 'published' ? 'Yayında' : 'Taslak' ?>
                                </span>
                            </td>
                            <td class="text-right actions-cell">
                                <a href="/projeler/<?= Helpers::e((string) $pr['slug']) ?>" target="_blank" class="btn btn-secondary btn-xs">Gör</a>
                                <a href="/admin/projeler/<?= (int) $pr['id'] ?>/duzenle" class="btn btn-secondary btn-xs">Düzenle</a>
                                <form action="/admin/projeler/<?= (int) $pr['id'] ?>/sil" method="post" onsubmit="return confirm('Bu projeyi ve tüm medya ilişkilerini silmek istediğinize emin misiniz?');">
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
