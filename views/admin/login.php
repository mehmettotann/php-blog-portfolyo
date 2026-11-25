<?php
use App\Helpers;

$flashes = Helpers::getFlashes();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= Helpers::e($pageTitle ?? 'Yönetim Girişi') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        (function () {
            var saved = localStorage.getItem('bp-theme');
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', saved || (prefersDark ? 'dark' : 'light'));
        })();
    </script>
    <link rel="stylesheet" href="/assets/css/style.css?v=1.0">
    <link rel="stylesheet" href="/assets/css/admin.css?v=1.0">
</head>
<body class="login-page">
    <main class="login-card">
        <div class="login-header">
            <span class="brand-mark">BP</span>
            <h1>Yönetim Paneli</h1>
            <p class="muted-text">example.com içerik yönetimi</p>
        </div>

        <?php foreach ($flashes as $fl): ?>
            <div class="alert alert-<?= Helpers::e($fl['type']) ?>" role="status">
                <?= Helpers::e($fl['message']) ?>
            </div>
        <?php endforeach; ?>

        <form action="/admin/giris" method="post" class="form-stack">
            <?= Helpers::csrfField() ?>

            <div class="form-group">
                <label for="username">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" required autofocus autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Giriş Yap</button>
        </form>

        <div class="login-footer">
            <a href="/">← Siteye Dön</a>
        </div>
    </main>
</body>
</html>
