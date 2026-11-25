<?php
use App\Helpers;
?>

<div class="admin-card">
    <div class="admin-card-head">
        <h2>İletişim Mesajları (<?= count($messages) ?>)</h2>
    </div>

    <?php if (empty($messages)): ?>
        <p class="muted-text">Henüz gelen bir mesaj yok.</p>
    <?php else: ?>
        <div class="message-list">
            <?php foreach ($messages as $m): ?>
                <article class="message-item <?= !$m['is_read'] ? 'is-unread' : '' ?>">
                    <div class="message-head">
                        <div>
                            <strong><?= Helpers::e((string) $m['name']) ?></strong>
                            <a href="mailto:<?= Helpers::e((string) $m['email']) ?>" class="row-meta"><?= Helpers::e((string) $m['email']) ?></a>
                            <div class="message-subject"><?= Helpers::e((string) $m['subject']) ?></div>
                        </div>
                        <div class="message-meta-right">
                            <span class="status-pill <?= !$m['is_read'] ? 'status-unread' : 'status-read' ?>">
                                <?= !$m['is_read'] ? 'Okunmadı' : 'Okundu' ?>
                            </span>
                            <span class="row-meta"><?= Helpers::formatDateTimeTr((string) $m['created_at']) ?></span>
                        </div>
                    </div>

                    <div class="message-body"><?= nl2br(Helpers::e((string) $m['message']), false) ?></div>

                    <div class="message-actions">
                        <a href="mailto:<?= Helpers::e((string) $m['email']) ?>?subject=Re:%20<?= rawurlencode((string) $m['subject']) ?>" class="btn btn-secondary btn-xs">E-posta ile Yanıtla</a>
                        <form action="/admin/mesajlar/<?= (int) $m['id'] ?>/okundu" method="post">
                            <?= Helpers::csrfField() ?>
                            <button type="submit" class="btn btn-secondary btn-xs">
                                <?= !$m['is_read'] ? 'Okundu İşaretle' : 'Okunmadı İşaretle' ?>
                            </button>
                        </form>
                        <form action="/admin/mesajlar/<?= (int) $m['id'] ?>/sil" method="post" onsubmit="return confirm('Bu mesajı silmek istediğinize emin misiniz?');">
                            <?= Helpers::csrfField() ?>
                            <button type="submit" class="btn btn-danger-outline btn-xs">Sil</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
