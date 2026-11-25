<?php
use App\Helpers;

$buildQuery = static function (array $overrides) use ($categorySlug, $search): string {
    $params = [];
    $cat = array_key_exists('kategori', $overrides) ? $overrides['kategori'] : $categorySlug;
    $q = array_key_exists('q', $overrides) ? $overrides['q'] : $search;
    $p = $overrides['sayfa'] ?? null;

    if ($cat !== null && $cat !== '') $params['kategori'] = $cat;
    if ($q !== null && $q !== '') $params['q'] = $q;
    if ($p !== null && (int) $p > 1) $params['sayfa'] = (int) $p;

    return $params === [] ? '/yazilar' : ('/yazilar?' . http_build_query($params));
};
?>

<section class="page-header">
    <div class="container">
        <span class="section-kicker">Blog &amp; Notlar</span>
        <h1><?= $activeCategory ? Helpers::e((string) $activeCategory['name']) : 'Yazılar' ?></h1>
        <p class="lead">
            <?= $activeCategory && !empty($activeCategory['description'])
                ? Helpers::e((string) $activeCategory['description'])
                : 'Deneyimler, öğrenme notları, teknoloji incelemeleri ve fikir paylaşımları.' ?>
        </p>

        <div class="filter-toolbar">
            <div class="category-pills" role="navigation" aria-label="Kategori filtresi">
                <a href="<?= Helpers::e($buildQuery(['kategori' => '', 'sayfa' => 1])) ?>"
                   class="pill <?= $categorySlug === '' ? 'is-active' : '' ?>">Tümü</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="<?= Helpers::e($buildQuery(['kategori' => $cat['slug'], 'sayfa' => 1])) ?>"
                       class="pill <?= $categorySlug === $cat['slug'] ? 'is-active' : '' ?>">
                        <?= Helpers::e((string) $cat['name']) ?>
                        <span class="pill-count"><?= (int) $cat['post_count'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <form action="/yazilar" method="get" class="search-form" role="search">
                <?php if ($categorySlug !== ''): ?>
                    <input type="hidden" name="kategori" value="<?= Helpers::e($categorySlug) ?>">
                <?php endif; ?>
                <input type="search" name="q" value="<?= Helpers::e($search) ?>" placeholder="Yazılarda ara..." aria-label="Yazılarda ara">
                <button type="submit" class="btn btn-secondary btn-sm">Ara</button>
            </form>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if ($search !== ''): ?>
            <p class="search-summary">
                <strong>"<?= Helpers::e($search) ?>"</strong> araması için <?= (int) $total ?> sonuç bulundu.
                <a href="<?= Helpers::e($buildQuery(['q' => '', 'sayfa' => 1])) ?>">Aramayı temizle</a>
            </p>
        <?php endif; ?>

        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <p>Bu kriterlere uygun yazı bulunamadı.</p>
                <a href="/yazilar" class="btn btn-secondary btn-sm">Tüm Yazılara Dön</a>
            </div>
        <?php else: ?>
            <div class="card-grid card-grid-3">
                <?php foreach ($posts as $post): ?>
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
                            <h2 class="card-title">
                                <a href="/yazilar/<?= Helpers::e((string) $post['slug']) ?>"><?= Helpers::e((string) $post['title']) ?></a>
                            </h2>
                            <?php if (!empty($post['excerpt'])): ?>
                                <p class="card-excerpt"><?= Helpers::e((string) $post['excerpt']) ?></p>
                            <?php endif; ?>
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
