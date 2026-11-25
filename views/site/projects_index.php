<?php
use App\Helpers;

$buildQuery = static function (array $overrides) use ($activeTag): string {
    $params = [];
    $tag = array_key_exists('etiket', $overrides) ? $overrides['etiket'] : $activeTag;
    $p = $overrides['sayfa'] ?? null;
    if ($tag !== null && $tag !== '') $params['etiket'] = $tag;
    if ($p !== null && (int) $p > 1) $params['sayfa'] = (int) $p;
    return $params === [] ? '/projeler' : ('/projeler?' . http_build_query($params));
};
?>

<section class="page-header">
    <div class="container">
        <span class="section-kicker">Dijital Portfolyo</span>
        <h1>Projeler &amp; Çalışmalarım</h1>
        <p class="lead">Geliştirdiğim yazılım projeleri, teknik deneyler, hobi çalışmaları ve demo videoları.</p>

        <?php if (!empty($allTags)): ?>
            <div class="category-pills" role="navigation" aria-label="Teknoloji filtresi">
                <a href="/projeler" class="pill <?= $activeTag === '' ? 'is-active' : '' ?>">Tümü</a>
                <?php foreach ($allTags as $t): ?>
                    <a href="<?= Helpers::e($buildQuery(['etiket' => $t, 'sayfa' => 1])) ?>"
                       class="pill <?= mb_strtolower($activeTag) === mb_strtolower($t) ? 'is-active' : '' ?>">
                        <?= Helpers::e($t) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (empty($projects)): ?>
            <div class="empty-state">
                <p>Bu filtreye uygun proje bulunamadı.</p>
                <a href="/projeler" class="btn btn-secondary btn-sm">Tüm Projeleri Gör</a>
            </div>
        <?php else: ?>
            <div class="card-grid card-grid-2">
                <?php foreach ($projects as $project): ?>
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
                                <?php if (!empty($project['is_featured'])): ?>
                                    <span class="badge-featured">Öne Çıkan</span>
                                <?php endif; ?>
                            </div>
                            <h2 class="card-title">
                                <a href="/projeler/<?= Helpers::e((string) $project['slug']) ?>"><?= Helpers::e((string) $project['title']) ?></a>
                            </h2>
                            <p class="card-excerpt"><?= Helpers::e((string) $project['summary']) ?></p>

                            <?php if (!empty($tags)): ?>
                                <div class="chip-row">
                                    <?php foreach ($tags as $t): ?>
                                        <span class="tag-mono"><?= Helpers::e($t) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="card-footer-actions">
                                <a href="/projeler/<?= Helpers::e((string) $project['slug']) ?>" class="link-arrow">İncele →</a>
                                <div class="ext-links">
                                    <?php if (!empty($project['live_url'])): ?>
                                        <a href="<?= Helpers::safeHref((string) $project['live_url']) ?>" target="_blank" rel="noopener noreferrer" class="ext-link">Canlı ↗</a>
                                    <?php endif; ?>
                                    <?php if (!empty($project['source_url'])): ?>
                                        <a href="<?= Helpers::safeHref((string) $project['source_url']) ?>" target="_blank" rel="noopener noreferrer" class="ext-link">Kaynak Kod ↗</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="pagination" aria-label="Sayfalama">
                    <?php if ($page > 1): ?>
                        <a href="<?= Helpers::e($buildQuery(['sayfa' => $page - 1])) ?>" class="page-link">← Önceki</a>
                    <?php endif; ?>
                    <span class="page-info">Sayfa <?= (int) $page ?> / <?= (int) $totalPages ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= Helpers::e($buildQuery(['sayfa' => $page + 1])) ?>" class="page-link">Sonraki →</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
