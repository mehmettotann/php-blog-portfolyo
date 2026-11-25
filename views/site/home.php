<?php
use App\Helpers;

$heroBadge = $settings['hero_badge'] ?? 'Merhaba, hoş geldiniz';
$heroTitle = $settings['hero_title'] ?? 'Ben Adınız Soyadınız. Üretiyor, öğreniyor ve deneyimlerimi paylaşıyorum.';
$heroSubtitle = $settings['hero_subtitle'] ?? 'Web teknolojileri, yazılım geliştirme, dijital ürünler ve merak ettiğim konular üzerine notlarımı, incelemelerimi ve projelerimi burada bir araya getiriyorum.';
?>

<section class="hero">
    <div class="container hero-inner">
        <div class="hero-content">
            <span class="hero-badge"><?= Helpers::e($heroBadge) ?></span>
            <h1 class="hero-title"><?= Helpers::e($heroTitle) ?></h1>
            <p class="hero-subtitle"><?= Helpers::e($heroSubtitle) ?></p>

            <div class="hero-actions">
                <a href="/projeler" class="btn btn-primary">Projeleri İncele</a>
                <a href="/yazilar" class="btn btn-secondary">Son Yazıları Oku</a>
                <a href="/hakkimda" class="btn btn-ghost">Hakkımda →</a>
            </div>

            <?php if (!empty($interests)): ?>
                <div class="hero-interests">
                    <span class="meta-label">İlgi Alanları:</span>
                    <div class="chip-row">
                        <?php foreach (array_slice($interests, 0, 6) as $interest): ?>
                            <span class="chip"><?= Helpers::e((string) $interest) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="hero-card">
            <div class="profile-avatar" aria-hidden="true">BP</div>
            <div class="profile-card-meta">
                <h2><?= Helpers::e($settings['site_title'] ?? 'Adınız Soyadınız') ?></h2>
                <p><?= Helpers::e($settings['site_tagline'] ?? 'Yazılım, Teknoloji & Dijital Portfolyo') ?></p>
                <?php if (!empty($settings['contact_location'])): ?>
                    <span class="location-pill"><?= Helpers::e($settings['contact_location']) ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($skills)): ?>
                <div class="profile-card-skills">
                    <?php foreach (array_slice($skills, 0, 6) as $skill): ?>
                        <span class="tag-mono"><?= Helpers::e((string) $skill) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="profile-card-footer">
                <a href="/iletisim" class="link-arrow">Birlikte çalışalım veya selam ver →</a>
            </div>
        </div>
    </div>
</section>

<!-- Son Blog Yazıları -->
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-kicker">Blog & Notlar</span>
                <h2>Son Yayınlanan Yazılar</h2>
            </div>
            <a href="/yazilar" class="link-arrow">Tüm yazılar →</a>
        </div>

        <?php if (empty($latestPosts)): ?>
            <div class="empty-state">Henüz yayınlanmış bir blog yazısı bulunmuyor.</div>
        <?php else: ?>
            <div class="card-grid card-grid-3">
                <?php foreach ($latestPosts as $post): ?>
                    <article class="post-card">
                        <a href="/yazilar/<?= Helpers::e((string) $post['slug']) ?>" class="card-cover-link">
                            <?php if (!empty($post['cover_image'])): ?>
                                <img src="<?= Helpers::e((string) $post['cover_image']) ?>" alt="<?= Helpers::e((string) $post['title']) ?>" loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="card-cover-placeholder" aria-hidden="true">BP</div>
                            <?php endif; ?>
                        </a>
                        <div class="card-body">
                            <div class="card-meta">
                                <?php if (!empty($post['category_name'])): ?>
                                    <a href="/yazilar?kategori=<?= Helpers::e((string) $post['category_slug']) ?>" class="badge-category">
                                        <?= Helpers::e((string) $post['category_name']) ?>
                                    </a>
                                <?php endif; ?>
                                <span><?= Helpers::formatDateTr((string) $post['published_at']) ?></span>
                                <span>· <?= (int) $post['reading_time'] ?> dk</span>
                            </div>
                            <h3 class="card-title">
                                <a href="/yazilar/<?= Helpers::e((string) $post['slug']) ?>"><?= Helpers::e((string) $post['title']) ?></a>
                            </h3>
                            <?php if (!empty($post['excerpt'])): ?>
                                <p class="card-excerpt"><?= Helpers::e((string) $post['excerpt']) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Öne Çıkan Projeler -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-kicker">Dijital Vitrin</span>
                <h2>Öne Çıkan Projeler & Çalışmalar</h2>
            </div>
            <a href="/projeler" class="link-arrow">Tüm projeler →</a>
        </div>

        <?php if (empty($featuredProjects)): ?>
            <div class="empty-state">Henüz yayınlanmış bir proje bulunmuyor.</div>
        <?php else: ?>
            <div class="card-grid card-grid-2">
                <?php foreach ($featuredProjects as $project): ?>
                    <?php
                    $tags = array_values(array_filter(array_map('trim', explode(',', (string) ($project['technologies'] ?? '')))));
                    ?>
                    <article class="project-card">
                        <a href="/projeler/<?= Helpers::e((string) $project['slug']) ?>" class="card-cover-link project-cover">
                            <?php if (!empty($project['cover_image'])): ?>
                                <img src="<?= Helpers::e((string) $project['cover_image']) ?>" alt="<?= Helpers::e((string) $project['title']) ?>" loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="card-cover-placeholder" aria-hidden="true">Proje</div>
                            <?php endif; ?>
                            <div class="media-badges">
                                <?php if ((int) $project['image_count'] > 0): ?>
                                    <span class="media-badge"><?= (int) $project['image_count'] ?> görsel</span>
                                <?php endif; ?>
                                <?php if ((int) $project['video_count'] > 0): ?>
                                    <span class="media-badge media-badge-video">▶ <?= (int) $project['video_count'] ?> video</span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="card-body">
                            <div class="card-meta">
                                <?php if (!empty($project['project_year'])): ?>
                                    <span class="tag-mono"><?= Helpers::e((string) $project['project_year']) ?></span>
                                <?php endif; ?>
                            </div>
                            <h3 class="card-title">
                                <a href="/projeler/<?= Helpers::e((string) $project['slug']) ?>"><?= Helpers::e((string) $project['title']) ?></a>
                            </h3>
                            <p class="card-excerpt"><?= Helpers::e((string) $project['summary']) ?></p>

                            <?php if (!empty($tags)): ?>
                                <div class="chip-row">
                                    <?php foreach (array_slice($tags, 0, 5) as $t): ?>
                                        <span class="tag-mono"><?= Helpers::e($t) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="card-footer-actions">
                                <a href="/projeler/<?= Helpers::e((string) $project['slug']) ?>" class="link-arrow">Detayları gör →</a>
                                <?php if (!empty($project['live_url'])): ?>
                                    <a href="<?= Helpers::safeHref((string) $project['live_url']) ?>" target="_blank" rel="noopener noreferrer" class="ext-link">Canlı ↗</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
