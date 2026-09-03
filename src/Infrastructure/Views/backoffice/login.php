<?php
// src/Infrastructure/Views/backoffice/login.php
use App\Infrastructure\Services\Csrf;
?>
<main class="backoffice-page backoffice-login">
    <div class="container backoffice-login-shell">
        <h3 class="backoffice-page-title">Espace professionnel</h3>
        <p>Connectez-vous pour gérer votre fiche Canal du Midi.</p>

        <?php if (!empty($error)): ?>
            <div class="backoffice-alert backoffice-alert--error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL . $lang ?>/backoffice/login" class="backoffice-form">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

            <label for="bo-email">Email</label>
            <input type="email" id="bo-email" name="email" required autocomplete="username">

            <label for="bo-password">Mot de passe</label>
            <input type="password" id="bo-password" name="password" required autocomplete="current-password">

            <button type="submit" class="button button-primary">Se connecter</button>
        </form>
    </div>
</main>
