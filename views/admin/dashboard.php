<?php
use App\Helpers;
?>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-label">Yayındaki Yazılar</span>
        <strong class="stat-value"><?= (int) $stats['posts_published'] ?></strong>
        <span class="stat-sub"><?= (int) $stats['posts_draft'] ?> taslak</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Projeler</span>
        <strong class="stat-value"><?= (int) $stats['projects_total'] ?></strong>
        <a href="/admin/projeler" class="stat-sub">Projeleri yönet →</a>
    </div>
    <div class="stat-card">
        <span class="stat-label">Medya Dosyaları</span>
        <strong class="stat-value"><?= (int) $stats['media_total'] ?></strong>
        <a href="/admin/medya" class="stat-sub">Kütüphaneyi aç →</a>
    </div>
    <div class="stat-card <?= (int) $stats['messages_unread'] > 0 ? 'stat-card-accent' : '' ?>">
        <span class="stat-label">Okunmamış Mesaj</span>
        <strong class="stat-value"><?= (int) $stats['messages_unread'] ?></strong>
        <a href="/admin/mesajlar" class="stat-sub">Toplam <?= (int) $stats['messages_total'] ?> mesaj →</a>
    </div>
</div>

<div class="admin-grid-2">
    <section class="admin-card">
        <div class="admin-card-head">
            <h2>Son Yazılar</h2>
            <a href="/admin/yazilar/yeni" class="btn btn-secondary btn-xs">+ Yeni Yazı</a>
        </div>
        <?php if (empty($recentPosts)): ?>
            <p class="muted-text">Henüz yazı eklenmemiş.</p>
        <?php else: ?>
            <div class="compact-list">
                <?php foreach ($recentPosts as $p): ?>
                    <div class="compact-row">
                        <div>
                            <a href="/admin/yazilar/<?= (int) $p['id'] ?>/duzenle" class="row-title"><?= Helpers::e((string) $p['title']) ?></a>
                            <div class="row-meta">
                                <span><?= Helpers::e((string) ($p['category_name'] ?? 'Kategorisiz')) ?></span>
                                <span>· <?= Helpers::formatDateTr((string) $p['updated_at']) ?></span>
                            </div>
                        </div>
                        <span class="status-pill status-<?= Helpers::e((string) $p['status']) ?>">
                            <?= $p['status'] === 'published' ? 'Yayında' : 'Taslak' ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <h2>Son Mesajlar</h2>
            <a href="/admin/mesajlar" class="btn btn-secondary btn-xs">Tümü</a>
        </div>
        <?php if (empty($recentMessages)): ?>
            <p class="muted-text">Henüz iletişim mesajı gelmemiş.</p>
        <?php else: ?>
            <div class="compact-list">
                <?php foreach ($recentMessages as $m): ?>
                    <div class="compact-row">
                        <div>
                            <a href="/admin/mesajlar" class="row-title">
                                <?= !$m['is_read'] ? '● ' : '' ?><?= Helpers::e((string) $m['name']) ?>
                            </a>
                            <div class="row-meta">
                                <span><?= Helpers::e((string) $m['subject']) ?></span>
                                <span>· <?= Helpers::formatDateTimeTr((string) $m['created_at']) ?></span>
                            </div>
                        </div>
                        <span class="status-pill <?= !$m['is_read'] ? 'status-unread' : 'status-read' ?>">
                            <?= !$m['is_read'] ? 'Yeni' : 'Okundu' ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<section class="admin-card">
    <div class="admin-card-head">
        <h2>Sunucu &amp; Yükleme Durumu</h2>
    </div>
    <div class="server-info-grid">
        <div><span class="meta-label">PHP Sürümü</span><strong><?= Helpers::e($serverInfo['php_version']) ?></strong></div>
        <div><span class="meta-label">upload_max_filesize</span><strong><?= Helpers::e($serverInfo['upload_max_filesize']) ?></strong></div>
        <div><span class="meta-label">post_max_size</span><strong><?= Helpers::e($serverInfo['post_max_size']) ?></strong></div>
        <div><span class="meta-label">memory_limit</span><strong><?= Helpers::e($serverInfo['memory_limit']) ?></strong></div>
        <div><span class="meta-label">LiteSpeed Cache</span><strong><?= Helpers::e($serverInfo['litespeed_cache']) ?></strong></div>
    </div>
</section>
