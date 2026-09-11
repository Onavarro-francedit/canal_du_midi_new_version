<?php
// src/Infrastructure/Views/backoffice/admin_dashboard.php
use App\Infrastructure\Services\Csrf;
?>
<div class="admin-topbar">
    <span class="admin-topbar-brand">Canal du Midi <small>Administration</small></span>
    <?php if (!empty($currentUser)): ?>
        <div class="admin-topbar-user">
            <span><?= htmlspecialchars($currentUser['email'], ENT_QUOTES, 'UTF-8') ?></span>
            <a href="<?= BASE_URL . $lang ?>/backoffice/logout" class="button button-small button-ghost">Déconnexion</a>
        </div>
    <?php endif; ?>
</div>

<main class="backoffice-page">
    <h1>Vue d'ensemble</h1>

    <?php if (!empty($message)): ?>
        <div class="backoffice-alert backoffice-alert--success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="backoffice-alert backoffice-alert--error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="admin-stats">
        <div class="admin-stat-card">
            <i class="bi bi-signpost-2"></i>
            <div>
                <span class="admin-stat-value"><?= count($allListings) ?></span>
                <span class="admin-stat-label">Fiches</span>
            </div>
        </div>
        <div class="admin-stat-card">
            <i class="bi bi-people"></i>
            <div>
                <span class="admin-stat-value"><?= count($owners) ?></span>
                <span class="admin-stat-label">Comptes propriétaires</span>
            </div>
        </div>
    </div>

    <section class="admin-card">
        <h2><i class="bi bi-list-ul"></i> Fiches</h2>
        <form method="get" action="<?= BASE_URL . $lang ?>/backoffice">
            <input type="text" name="q" placeholder="Rechercher une fiche..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="button button-small">Rechercher</button>
        </form>

        <table class="backoffice-table">
            <thead><tr><th>Titre</th><th>Ville</th><th></th></tr></thead>
            <tbody>
                <?php foreach (array_slice($allListings, 0, 50) as $listing): ?>
                    <tr>
                        <td><?= htmlspecialchars($listing->translations['title'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($listing->contact['ville'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><a href="<?= BASE_URL . $lang ?>/backoffice?id=<?= (int) $listing->id ?>" class="button button-small button-ghost">Modifier</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="admin-card">
        <h2><i class="bi bi-person-plus"></i> Créer un compte établissement</h2>
        <form method="post" action="<?= BASE_URL . $lang ?>/backoffice" class="backoffice-form">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="create_owner">

            <label for="bo-owner-listing">Fiche</label>
            <select id="bo-owner-listing" name="listing_id" required>
                <option value="">— Choisir —</option>
                <?php foreach (array_slice($allListings, 0, 200) as $listing): ?>
                    <option value="<?= (int) $listing->id ?>"><?= htmlspecialchars($listing->translations['title'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>

            <label for="bo-owner-email">Email du compte</label>
            <input type="email" id="bo-owner-email" name="owner_email" required>

            <label for="bo-owner-password">Mot de passe temporaire</label>
            <input type="text" id="bo-owner-password" name="owner_password" required minlength="8">

            <button type="submit" class="button button-primary">Créer le compte</button>
        </form>
    </section>

    <section class="admin-card">
        <h2><i class="bi bi-key"></i> Comptes établissements existants</h2>
        <table class="backoffice-table">
            <thead><tr><th>Email</th><th>Fiche</th><th>Réinitialiser mot de passe</th></tr></thead>
            <tbody>
                <?php foreach ($owners as $owner): ?>
                    <tr>
                        <td><?= htmlspecialchars($owner['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($owner['listing_title'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <form method="post" action="<?= BASE_URL . $lang ?>/backoffice" class="backoffice-inline-form">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="reset_password">
                                <input type="hidden" name="listing_id" value="<?= (int) $owner['listing_id'] ?>">
                                <input type="text" name="new_password" placeholder="Nouveau mot de passe" minlength="8" required>
                                <button type="submit" class="button button-small">Réinitialiser</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
