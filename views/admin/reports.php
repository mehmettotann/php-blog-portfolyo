<?php
use App\Helpers;

$period = (string) ($report['period'] ?? 'month');
$typeFilter = (string) ($report['typeFilter'] ?? 'all');
$summary = $report['summary'] ?? ['unique_visitors' => 0, 'total_hits' => 0, 'total_likes' => 0];
$ov = $report['periodsOverview'] ?? [];
$pages = $report['pages'] ?? [];
$trend = $report['trend'] ?? [];
$topPosts = $report['topLikedPosts'] ?? [];
$topProjects = $report['topLikedProjects'] ?? [];

$periodLabels = [
    'day'   => 'Bugün (Günlük)',
    'week'  => 'Son 7 Gün (Haftalık)',
    'month' => 'Son 30 Gün (Aylık)',
    'year'  => 'Son 1 Yıl (Yıllık)',
    'all'   => 'Tüm Zamanlar',
];

$typeLabels = [
    'all'     => 'Tüm Sayfalar',
    'post'    => 'Blog Yazıları',
    'project' => 'Projeler',
    'page'    => 'Sabit Sayfalar',
    'home'    => 'Ana Sayfa',
];

$badgeNames = [
    'home'    => 'Ana Sayfa',
    'page'    => 'Sayfa',
    'post'    => 'Yazı',
    'project' => 'Proje',
];

$buildUrl = static function (array $overrides) use ($period, $typeFilter): string {
    $p = $overrides['donem'] ?? $period;
    $t = $overrides['tur'] ?? $typeFilter;
    return '/admin/raporlar?' . http_build_query(['donem' => $p, 'tur' => $t]);
};

$maxTrendHits = 1;
foreach ($trend as $tr) {
    $maxTrendHits = max($maxTrendHits, (int) ($tr['hits'] ?? 0));
}
?>

<?php if (!empty($tablesMissing)): ?>
    <div class="alert alert-warning">
        Analitik ve beğeni tabloları (<code>page_visit_visitors</code>, <code>content_likes</code>) veritabanında henüz oluşturulmamış.
        Kurulumu tamamlamak için <code>php database/install.php --no-seed</code> komutunu çalıştırın veya <code>database/schema.sql</code> içindeki son 2 tabloyu phpMyAdmin'den ekleyin.
    </div>
<?php endif; ?>

<!-- 4 Dönemlik Hızlı Özet: Günlük / Haftalık / Aylık / Yıllık -->
<div class="stat-grid">
    <?php
    $cards = [
        ['key' => 'day',   'title' => 'Günlük (Bugün)'],
        ['key' => 'week',  'title' => 'Haftalık (7 Gün)'],
        ['key' => 'month', 'title' => 'Aylık (30 Gün)'],
        ['key' => 'year',  'title' => 'Yıllık (365 Gün)'],
    ];
    foreach ($cards as $c):
        $u = (int) ($ov[$c['key']]['unique'] ?? 0);
        $h = (int) ($ov[$c['key']]['hits'] ?? 0);
        $isActive = ($period === $c['key']);
    ?>
        <a href="<?= Helpers::e($buildUrl(['donem' => $c['key']])) ?>" class="stat-card stat-card-link <?= $isActive ? 'stat-card-accent' : '' ?>">
            <span class="stat-label"><?= Helpers::e($c['title']) ?></span>
            <strong class="stat-value"><?= number_format($u, 0, ',', '.') ?> <small class="stat-unit">tekil</small></strong>
            <span class="stat-sub">Toplam tıklama: <strong><?= number_format($h, 0, ',', '.') ?></strong></span>
        </a>
    <?php endforeach; ?>
</div>

<!-- Filtre Çubuğu -->
<section class="admin-card">
    <div class="report-filters">
        <div class="filter-group">
            <span class="meta-label">Dönem:</span>
            <div class="category-pills" style="margin:0;">
                <?php foreach ($periodLabels as $k => $lbl): ?>
                    <a href="<?= Helpers::e($buildUrl(['donem' => $k])) ?>" class="pill <?= $period === $k ? 'is-active' : '' ?>"><?= Helpers::e($lbl) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="filter-group">
            <span class="meta-label">İçerik Türü:</span>
            <div class="category-pills" style="margin:0;">
                <?php foreach ($typeLabels as $k => $lbl): ?>
                    <a href="<?= Helpers::e($buildUrl(['tur' => $k])) ?>" class="pill <?= $typeFilter === $k ? 'is-active' : '' ?>"><?= Helpers::e($lbl) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="report-selection-summary">
        <div>
            <span class="meta-label">Seçili Dönem Tekil Ziyaretçi (Farklı IP)</span>
            <strong><?= number_format((int) $summary['unique_visitors'], 0, ',', '.') ?></strong>
        </div>
        <div>
            <span class="meta-label">Seçili Dönem Toplam Tıklama (Aynı IP Dahil)</span>
            <strong><?= number_format((int) $summary['total_hits'], 0, ',', '.') ?></strong>
        </div>
        <div>
            <span class="meta-label">Toplam Beğeni (Yazı + Proje)</span>
            <strong>♥ <?= number_format((int) $summary['total_likes'], 0, ',', '.') ?></strong>
        </div>
    </div>
</section>

<!-- Günlük Ziyaret Trendi -->
<?php if (!empty($trend)): ?>
<section class="admin-card">
    <div class="admin-card-head">
        <h2>Günlük Ziyaret Grafiği</h2>
        <div class="chart-legend">
            <span><i class="legend-dot legend-hits"></i> Toplam Tıklama</span>
            <span><i class="legend-dot legend-uniq"></i> Tekil Ziyaretçi</span>
        </div>
    </div>
    <div class="mini-bar-chart" role="img" aria-label="Günlük ziyaret grafiği">
        <?php foreach ($trend as $tr): ?>
            <?php
            $hits = (int) ($tr['hits'] ?? 0);
            $uniq = (int) ($tr['uniq'] ?? 0);
            $hitsPct = max(4, (int) round(($hits / $maxTrendHits) * 100));
            $uniqPct = max(4, (int) round(($uniq / $maxTrendHits) * 100));
            $dayLabel = substr((string) $tr['visit_date'], 5); // MM-DD
            ?>
            <div class="bar-col" title="<?= Helpers::e((string) $tr['visit_date']) ?> · Tekil: <?= $uniq ?> · Toplam: <?= $hits ?>">
                <div class="bar-pair">
                    <div class="bar bar-hits" style="height: <?= $hitsPct ?>%;"></div>
                    <div class="bar bar-uniq" style="height: <?= $uniqPct ?>%;"></div>
                </div>
                <span class="bar-label"><?= Helpers::e($dayLabel) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Sayfa / Yazı / Proje Bazlı Ziyaret Tablosu -->
<section class="admin-card">
    <div class="admin-card-head">
        <h2>Sayfa, Yazı &amp; Proje Ziyaretleri (<?= Helpers::e($periodLabels[$period] ?? '') ?>)</h2>
        <span class="row-meta"><?= count($pages) ?> kayıt</span>
    </div>

    <?php if (empty($pages)): ?>
        <p class="muted-text">Seçili dönem ve filtrede henüz ziyaret kaydı bulunmuyor. Ziyaretçiler sayfalara girdikçe veriler otomatik olarak burada görünecektir.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Sayfa / İçerik</th>
                        <th>Tür</th>
                        <th class="text-right">Tekil (Farklı IP)</th>
                        <th class="text-right">Toplam Tıklama</th>
                        <th class="text-right">Beğeni</th>
                        <th class="text-right">Son Ziyaret</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $row): ?>
                        <tr>
                            <td>
                                <a href="<?= Helpers::e((string) $row['path']) ?>" target="_blank" class="row-title">
                                    <?= Helpers::e((string) ($row['page_title'] ?: $row['path'])) ?>
                                </a>
                                <div class="row-meta"><?= Helpers::e((string) $row['path']) ?></div>
                            </td>
                            <td>
                                <span class="tag-mono"><?= Helpers::e($badgeNames[$row['page_type']] ?? (string) $row['page_type']) ?></span>
                            </td>
                            <td class="text-right"><strong><?= number_format((int) $row['unique_visitors'], 0, ',', '.') ?></strong></td>
                            <td class="text-right"><?= number_format((int) $row['total_hits'], 0, ',', '.') ?></td>
                            <td class="text-right">
                                <?php if (in_array($row['page_type'], ['post', 'project'], true)): ?>
                                    <span class="like-pill">♥ <?= (int) $row['likes'] ?></span>
                                <?php else: ?>
                                    <span class="row-meta">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right row-meta"><?= Helpers::formatDateTimeTr((string) $row['last_seen_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- Beğeni Sıralaması -->
<div class="admin-grid-2">
    <section class="admin-card">
        <div class="admin-card-head">
            <h2>Yazı Beğenileri</h2>
        </div>
        <?php if (empty($topPosts)): ?>
            <p class="muted-text">Henüz yayında yazı yok.</p>
        <?php else: ?>
            <div class="compact-list">
                <?php foreach ($topPosts as $tp): ?>
                    <div class="compact-row">
                        <a href="/yazilar/<?= Helpers::e((string) $tp['slug']) ?>" target="_blank" class="row-title"><?= Helpers::e((string) $tp['title']) ?></a>
                        <span class="like-pill">♥ <?= (int) $tp['like_count'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <h2>Proje Beğenileri</h2>
        </div>
        <?php if (empty($topProjects)): ?>
            <p class="muted-text">Henüz yayında proje yok.</p>
        <?php else: ?>
            <div class="compact-list">
                <?php foreach ($topProjects as $tpr): ?>
                    <div class="compact-row">
                        <a href="/projeler/<?= Helpers::e((string) $tpr['slug']) ?>" target="_blank" class="row-title"><?= Helpers::e((string) $tpr['title']) ?></a>
                        <span class="like-pill">♥ <?= (int) $tpr['like_count'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
