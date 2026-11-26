<?php
use App\Auth;
use App\Helpers;
use App\MediaRenderer;
?>

<article class="project-detail">
    <header class="page-header">
        <div class="container">
            <div class="breadcrumb">
                <a href="/projeler">← Tüm Projeler</a>
                <?php if (!empty($project['project_year'])): ?>
                    <span>· <?= Helpers::e((string) $project['project_year']) ?></span>
                <?php endif; ?>
                <?php if ($project['status'] !== 'published'): ?>
                    <span class="badge-draft">Taslak</span>
                <?php endif; ?>
            </div>

            <h1><?= Helpers::e((string) $project['title']) ?></h1>
            <p class="lead"><?= Helpers::e((string) $project['summary']) ?></p>

            <?php if (!empty($tags)): ?>
                <div class="chip-row project-tags">
                    <?php foreach ($tags as $t): ?>
                        <a href="/projeler?etiket=<?= urlencode($t) ?>" class="tag-mono"><?= Helpers::e($t) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="project-cta-row">
                <?php if (!empty($project['live_url'])): ?>
                    <a href="<?= Helpers::safeHref((string) $project['live_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                        Canlı Önizleme ↗
                    </a>
                <?php endif; ?>
                <?php if (!empty($project['source_url'])): ?>
                    <a href="<?= Helpers::safeHref((string) $project['source_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">
                        Kaynak Kodu (GitHub) ↗
                    </a>
                <?php endif; ?>
                <?php if (Auth::isLoggedIn()): ?>
                    <a href="/admin/projeler/<?= (int) $project['id'] ?>/duzenle" class="btn btn-ghost">Projeyi Düzenle</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php if (!empty($project['cover_image'])): ?>
        <div class="container">
            <figure class="project-hero-media">
                <button type="button" class="lightbox-trigger" data-lightbox-src="<?= Helpers::e((string) $project['cover_image']) ?>" data-lightbox-caption="<?= Helpers::e((string) $project['title']) ?>" aria-label="Proje kapak görselini büyüt">
                    <img src="<?= Helpers::e((string) $project['cover_image']) ?>" alt="<?= Helpers::e((string) $project['title']) ?>">
                </button>
            </figure>
        </div>
    <?php endif; ?>

    <!-- Proje Açıklama Blokları -->
    <?php if (!empty($blocks)): ?>
        <section class="section">
            <div class="container container-narrow">
                <div class="prose" data-lightbox-group="project-prose">
                    <?= MediaRenderer::renderBlocks($blocks) ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Proje Videoları (Kendi Videolarım + Harici YouTube/Vimeo) -->
    <?php if (!empty($videos)): ?>
        <section class="section section-alt">
            <div class="container">
                <div class="section-head">
                    <div>
                        <span class="section-kicker">Demo &amp; Tanıtım</span>
                        <h2>Proje Videoları</h2>
                    </div>
                </div>

                <div class="video-showcase-grid <?= count($videos) === 1 ? 'is-single' : '' ?>">
                    <?php foreach ($videos as $vid): ?>
                        <div class="video-showcase-card">
                            <div class="video-source-pill">
                                <?php if ($vid['media_type'] === 'video_local'): ?>
                                    <span>Yerel Video (HTML5)</span>
                                <?php else: ?>
                                    <span><?= $vid['provider'] === 'vimeo' ? 'Vimeo' : 'YouTube' ?></span>
                                <?php endif; ?>
                            </div>
                            <?php
                            if ($vid['media_type'] === 'video_local') {
                                echo MediaRenderer::renderLocalVideoPlayer(
                                    (string) ($vid['file_path'] ?? ''),
                                    $vid['poster_path'] !== null ? (string) $vid['poster_path'] : null,
                                    $vid['title'] !== null ? (string) $vid['title'] : null,
                                    $vid['caption'] !== null ? (string) $vid['caption'] : null
                                );
                            } else {
                                echo MediaRenderer::renderExternalVideoPlayer(
                                    (string) ($vid['external_url'] ?? ''),
                                    $vid['title'] !== null ? (string) $vid['title'] : null,
                                    $vid['caption'] !== null ? (string) $vid['caption'] : null,
                                    $vid['provider'] !== null ? (string) $vid['provider'] : null,
                                    $vid['video_id'] !== null ? (string) $vid['video_id'] : null
                                );
                            }
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Proje Görsel Galerisi (Lightbox Destekli) -->
    <?php if (!empty($galleryImages)): ?>
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <div>
                        <span class="section-kicker">Ekran Görüntüleri</span>
                        <h2>Proje Galerisi</h2>
                    </div>
                    <span class="muted-text">Büyütmek için görsellere tıklayın</span>
                </div>

                <div class="gallery-grid" data-lightbox-group="project-gallery">
                    <?php foreach ($galleryImages as $img): ?>
                        <?php $cap = trim((string) ($img['caption'] ?: ($img['title'] ?: $project['title']))); ?>
                        <figure class="gallery-item">
                            <button type="button" class="lightbox-trigger"
                                    data-lightbox-src="<?= Helpers::e((string) $img['file_path']) ?>"
                                    data-lightbox-caption="<?= Helpers::e($cap) ?>"
                                    aria-label="<?= Helpers::e($cap) ?> görselini büyüt">
                                <img src="<?= Helpers::e((string) $img['file_path']) ?>" alt="<?= Helpers::e($cap) ?>" loading="lazy" decoding="async">
                            </button>
                            <?php if (!empty($img['caption']) || !empty($img['title'])): ?>
                                <figcaption class="media-caption">
                                    <?= Helpers::e((string) ($img['caption'] ?: $img['title'])) ?>
                                </figcaption>
                            <?php endif; ?>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="section project-share-section">
        <div class="container">
            <?php
            $shareHeading = 'Bu projeyi paylaş';
            $shareTitle = (string) $project['title'];
            $shareUrl = (string) ($canonicalUrl ?? '');
            require __DIR__ . '/_share_bar.php';
            ?>
        </div>
    </section>
</article>
