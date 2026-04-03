<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($seo['title'] ?? 'Canal du Midi | Voyages et escapades') ?></title>
    <meta name="description" content="<?= htmlspecialchars($seo['description'] ?? 'Découvrez le Canal du Midi : croisières en péniche, circuits à vélo et hôtels de charme. Planifiez votre voyage sur mesure.') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($seo['keywords'] ?? 'Canal du Midi, voyage, tourisme, Occitanie, péniche, vélo, hôtel') ?>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/styles.css">
   
    <!-- Open Graph (Para que se vea bien en Facebook/WhatsApp) -->
    <meta property="og:title" content="<?= htmlspecialchars($seo['title'] ?? 'Canal du Midi | Voyages et escapades') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($seo['description'] ?? 'Découvrez le Canal du Midi : croisières en péniche, circuits à vélo et hôtels de charme. Planifiez votre voyage sur mesure.') ?>">
    <meta property="og:type" content="website">

    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
   
    <link rel="canonical" href="<?= BASE_URL . $lang . '/' . $page . (isset($params) ? '/' . $params : '') ?>">
    
    <script>
        // Pasamos las variables de PHP a JS globalmente
        const BASE_URL = "<?= BASE_URL ?>";
        const lang = "<?= $lang ?>";
    </script>
    <?php if (isset($page) && $page === 'service'): ?>
         <!-- Leaflet CSS -->
         <meta property="og:image" content="<?= htmlspecialchars($service->imageUrl ?? BASE_URL . 'public/assets/images/default_service.jpg') ?>">
         <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/service_detail.css">
            <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/calendar.css">
         <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <?php endif; ?>

    <?php if (isset($page) && $page === 'search'): ?>
        <!-- Leaflet CSS (Reutilizamos la misma librería que en el detalle) -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
        <!-- Tu nuevo CSS tipo MyListing -->
        <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/search.css">
    <?php endif; ?>


    <?php if (!isset($service) && $page !== 'home'): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/errors.css">
    <?php endif; ?>

    <?php if (isset($page) && $page === 'poi'): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/poi_detail.css">
    <?php endif; ?>


    <?php if (isset($page) && $page === 'home'): ?>
        <script type="application/ld+json">
            <?= \App\Infrastructure\Views\Helpers\SchemaGenerator::generateHome($lang) ?>
        </script>
    <?php elseif (isset($service) && $service instanceof \App\Domain\Models\Service): ?>
        <script type="application/ld+json">
            <?= \App\Infrastructure\Views\Helpers\SchemaGenerator::generate($service, $lang) ?>
        </script>
    <?php endif; ?>
</head>
<body>
    <?php
        $currentPage = $page ?? 'home';
        $currentParams = isset($params) && $params !== null ? '/' . ltrim((string)$params, '/') : '';
        $currentQueryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
        $languageLinks = [
            'fr' => BASE_URL . 'fr/' . $currentPage . $currentParams . $currentQueryString,
            'es' => BASE_URL . 'es/' . $currentPage . $currentParams . $currentQueryString,
            'en' => BASE_URL . 'en/' . $currentPage . $currentParams . $currentQueryString,
        ];
    ?>
    <div class="page-shell">
        <header class="site-header">
            <div class="container header-row">
                <a class="brand" href="<?= BASE_URL . $lang ?>/home">
                    <span class="brand-mark"></span>
                    <span class="brand-text">Canal du Midi</span>
                </a>
                <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">
                    <span></span><span></span>
                </button>
                <nav id="primary-nav" class="main-nav">
                    <a href="<?= BASE_URL . $lang ?>/home#destinations">Destinations</a>
                    <a href="<?= BASE_URL . $lang ?>/home#experiences">Expériences</a>
                    <a href="<?= BASE_URL . $lang ?>/home#why-us">Pourquoi nous</a>
                    <a href="<?= BASE_URL . $lang ?>/home#reviews">Avis</a>
                    <a href="<?= BASE_URL . $lang ?>/home#news">Actualités</a>
                </nav>
                <div class="lang-selector">
                    <label class="lang-selector-label" for="language-switcher">Choisir la langue</label>
                    <div class="lang-select-wrap">
                        <select
                            id="language-switcher"
                            class="lang-select"
                            aria-label="Choisir la langue"
                            onchange="if (this.value) window.location.href = this.value;"
                        >
                            <?php foreach ($languageLinks as $localeCode => $localeUrl): ?>
                                <option
                                    value="<?= htmlspecialchars($localeUrl) ?>"
                                    lang="<?= $localeCode ?>"
                                    <?= $lang === $localeCode ? 'selected' : '' ?>
                                >
                                    <?= strtoupper($localeCode) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="lang-select-caret" aria-hidden="true">
                            <i class="bi bi-chevron-down"></i>
                        </span>
                    </div>
                </div>
                <a class="button button-small button-ghost" href="#newsletter">S'inscrire</a>
            </div>
        </header>
        