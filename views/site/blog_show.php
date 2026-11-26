<?php
use App\Auth;
use App\Helpers;
use App\MediaRenderer;
?>

<article class="article-page">
    <header class="article-header">
        <div class="container container-narrow">
            <div class="article-meta">
                <?php if (!empty($post['category_name'])): ?>
                    <a href="/yazilar?kategori=<?= Helpers::e((string) $post['category_slug']) ?>" class="badge-category">
                        <?= Helpers::e((string) $post['category_name']) ?>
                    </a>
                <?php endif; ?>
                <time datetime="<?= Helpers::e((string) $post['published_at']) ?>">
                    <?= Helpers::formatDateTr((string) ($post['published_at'] ?: $post['created_at'])) ?>
                </time>
                <span>· <?= (int) $post['reading_time'] ?> dk okuma</span>
                <?php if ($post['status'] !== 'published'): ?>
                    <span class="badge-draft">Taslak Önizleme</span>
                <?php endif; ?>
            </div>

            <h1 class="article-title"><?= Helpers::e((string) $post['title']) ?></h1>

            <?php if (!empty($post['excerpt'])): ?>
                <p class="article-lead"><?= Helpers::e((string) $post['excerpt']) ?></p>
            <?php endif; ?>

            <?php if (Auth::isLoggedIn()): ?>
                <div class="admin-quick-edit">
                    <a href="/admin/yazilar/<?= (int) $post['id'] ?>/duzenle" class="btn btn-secondary btn-sm">Bu Yazıyı Düzenle</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!empty($post['cover_image'])): ?>
        <div class="container container-article-cover">
            <figure class="article-cover">
                <button type="button" class="lightbox-trigger" data-lightbox-src="<?= Helpers::e((string) $post['cover_image']) ?>" data-lightbox-caption="<?= Helpers::e((string) $post['title']) ?>" aria-label="Kapak görselini büyüt">
                    <img src="<?= Helpers::e((string) $post['cover_image']) ?>" alt="<?= Helpers::e((string) $post['title']) ?>">
                </button>
            </figure>
        </div>
    <?php endif; ?>

    <div class="container container-narrow">
        <div class="prose article-content" data-lightbox-group="article">
            <?= MediaRenderer::renderBlocks($blocks) ?>
        </div>

        <div class="inline-share-wrap">
            <?php
            if ($post['status'] === 'published') {
                $likeType = 'post';
                $likeId = (int) $post['id'];
                require __DIR__ . '/_like_button.php';
            }
            $shareHeading = 'Bu yazıyı paylaş';
            $shareTitle = (string) $post['title'];
            $shareUrl = (string) ($canonicalUrl ?? '');
            require __DIR__ . '/_share_bar.php';
            ?>
        </div>

        <footer class="article-footer">
            <a href="/yazilar" class="link-arrow">← Tüm yazılara dön</a>
            <a href="/iletisim" class="link-arrow">Bu yazı hakkında görüşünü paylaş →</a>
        </footer>
    </div>
</article>

<?php if (!empty($relatedPosts)): ?>
    <section class="section section-alt">
        <div class="container">
            <div class="section-head">
                <h2>İlgili Yazılar</h2>
                <a href="/yazilar" class="link-arrow">Tümü →</a>
            </div>
            <div class="card-grid card-grid-3">
                <?php foreach ($relatedPosts as $rel): ?>
                    <article class="post-card">
                        <a href="/yazilar/<?= Helpers::e((string) $rel['slug']) ?>" class="card-cover-link">
                            <?php if (!empty($rel['cover_image'])): ?>
                                <img src="<?= Helpers::e((string) $rel['cover_image']) ?>" alt="<?= Helpers::e((string) $rel['title']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="card-cover-placeholder" aria-hidden="true">BP</div>
                            <?php endif; ?>
                        </a>
                        <div class="card-body">
                            <div class="card-meta">
                                <?php if (!empty($rel['category_name'])): ?>
                                    <span class="badge-category"><?= Helpers::e((string) $rel['category_name']) ?></span>
                                <?php endif; ?>
                                <span><?= (int) $rel['reading_time'] ?> dk</span>
                            </div>
                            <h3 class="card-title">
                                <a href="/yazilar/<?= Helpers::e((string) $rel['slug']) ?>"><?= Helpers::e((string) $rel['title']) ?></a>
                            </h3>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
