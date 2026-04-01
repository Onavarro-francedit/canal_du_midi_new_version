<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Canal du Midi | Voyages et escapades' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/canal_du_midi/public/assets/css/styles.css">
    <link rel="stylesheet" href="/canal_du_midi/public/assets/css/errors.css">

    <?php if (isset($page) && $page === 'service'): ?>
        <link rel="stylesheet" href="/canal_du_midi/public/assets/css/service_detail.css">
    <?php endif; ?>

    <?php if (!isset($service) && $page !== 'home'): ?>
        <link rel="stylesheet" href="/canal_du_midi/public/assets/css/errors.css">
    <?php endif; ?>
</head>
<body>
    <div class="page-shell">
        <header class="site-header">
            <div class="container header-row">
                <a class="brand" href="#top">
                    <span class="brand-mark"></span>
                    <span class="brand-text">Canal du Midi</span>
                </a>
                <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">
                    <span></span><span></span>
                </button>
                <nav id="primary-nav" class="main-nav">
                    <a href="/canal_du_midi/<?= $lang ?>/home#destinations">Destinations</a>
                    <a href="/canal_du_midi/<?= $lang ?>/home#experiences">Expériences</a>
                    <a href="/canal_du_midi/<?= $lang ?>/home#why-us">Pourquoi nous</a>
                    <a href="/canal_du_midi/<?= $lang ?>/home#reviews">Avis</a>
                    <a href="/canal_du_midi/<?= $lang ?>/home#news">Actualités</a>
                </nav>
                <div class="lang-selector">
                    <a href="/canal_du_midi/fr/home">FR</a> | 
                    <a href="/canal_du_midi/es/home">ES</a> | 
                    <a href="/canal_du_midi/en/home">EN</a>
                </div>
                <a class="button button-small button-ghost" href="#newsletter">S'inscrire</a>
            </div>
        </header>