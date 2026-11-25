<?php
use App\Helpers;

$intro = $settings['about_intro'] ?? '';
$story = $settings['about_story'] ?? '';
$storyParagraphs = array_values(array_filter(array_map('trim', preg_split('/\R{2,}/', $story) ?: [])));
?>

<section class="page-header">
    <div class="container container-narrow">
        <span class="section-kicker">Hakkımda</span>
        <h1><?= Helpers::e($settings['site_title'] ?? 'Adınız Soyadınız') ?></h1>
        <?php if ($intro !== ''): ?>
            <p class="lead"><?= Helpers::e($intro) ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container about-layout">
        <div class="about-main">
            <div class="prose">
                <h2>Hikayem</h2>
                <?php if (!empty($storyParagraphs)): ?>
                    <?php foreach ($storyParagraphs as $para): ?>
                        <p><?= nl2br(Helpers::e($para), false) ?></p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Biyografi metni henüz eklenmemiş. Yönetim panelindeki Site &amp; Hakkımda Ayarları bölümünden kolayca düzenleyebilirsiniz.</p>
                <?php endif; ?>
            </div>

            <?php if (!empty($timeline)): ?>
                <div class="about-section">
                    <h2>Eğitim &amp; Deneyim</h2>
                    <div class="timeline">
                        <?php foreach ($timeline as $item): ?>
                            <div class="timeline-item">
                                <div class="timeline-period"><?= Helpers::e((string) ($item['period'] ?? '')) ?></div>
                                <div class="timeline-body">
                                    <h3><?= Helpers::e((string) ($item['role'] ?? '')) ?></h3>
                                    <?php if (!empty($item['place'])): ?>
                                        <div class="timeline-place"><?= Helpers::e((string) $item['place']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($item['detail'])): ?>
                                        <p><?= Helpers::e((string) $item['detail']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <aside class="about-sidebar">
            <?php if (!empty($skills)): ?>
                <div class="info-card">
                    <h3>Uzmanlık Alanları</h3>
                    <div class="chip-row">
                        <?php foreach ($skills as $skill): ?>
                            <span class="tag-mono"><?= Helpers::e((string) $skill) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($interests)): ?>
                <div class="info-card">
                    <h3>İlgi Alanları &amp; Hobiler</h3>
                    <div class="chip-row">
                        <?php foreach ($interests as $interest): ?>
                            <span class="chip"><?= Helpers::e((string) $interest) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="info-card">
                <h3>İletişime Geç</h3>
                <p class="muted-text">Yeni projeler, fikir alışverişi veya sadece merhaba demek için yazabilirsiniz.</p>
                <a href="/iletisim" class="btn btn-primary btn-block">Mesaj Gönder</a>
            </div>
        </aside>
    </div>
</section>
