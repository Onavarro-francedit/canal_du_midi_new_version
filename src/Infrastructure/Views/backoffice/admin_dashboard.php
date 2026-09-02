<?php
// src/Infrastructure/Views/backoffice/admin_dashboard.php
use App\Infrastructure\Services\Csrf;
?>
<main class="backoffice-page">
    <h1>Backoffice — Administration</h1>

    <?php if (!empty($message)): ?>
        <div class="backoffice-alert backoffice-alert--success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="backoffice-alert backoffice-alert--error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <section class="backoffice-fieldset">
        <h2>Fiches</h2>
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
                        <td><a href="<?= BASE_URL . $lang ?>/backoffice?id=<?= (int) $listing->id ?>">Modifier</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="backoffice-fieldset">
        <h2>Créer un compte établissement</h2>
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

    <section class="backoffice-fieldset">
        <h2>Comptes établissements existants</h2>
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
