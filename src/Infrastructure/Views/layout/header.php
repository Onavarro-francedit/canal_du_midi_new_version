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
                    <a href="#destinations">Destinations</a>
                    <a href="#experiences">Expériences</a>
                    <a href="#why-us">Pourquoi nous</a>
                    <a href="#reviews">Avis</a>
                    <a href="#news">Actualités</a>
                </nav>
                <a class="button button-small button-ghost" href="#newsletter">S'inscrire</a>
            </div>
        </header>