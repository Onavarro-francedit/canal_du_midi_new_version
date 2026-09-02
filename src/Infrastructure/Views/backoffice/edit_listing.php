<?php
// src/Infrastructure/Views/backoffice/edit_listing.php
use App\Infrastructure\Services\Csrf;

$categoryIds = array_column($service->categories, 'id');
$actionUrl = BASE_URL . $lang . '/backoffice' . ($isAdmin ? '?id=' . $service->id : '');
?>
<main class="backoffice-page">
    <h1>Modifier ma fiche</h1>

    <?php if ($saved): ?>
        <div class="backoffice-alert backoffice-alert--success">Modifications enregistrées.</div>
    <?php endif; ?>
    <?php if (!empty($formError)): ?>
        <div class="backoffice-alert backoffice-alert--error"><?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" class="backoffice-form">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

        <label for="bo-title">Titre</label>
        <input type="text" id="bo-title" name="title" required value="<?= htmlspecialchars($service->translations['title'], ENT_QUOTES, 'UTF-8') ?>">

        <label for="bo-description">Description</label>
        <textarea id="bo-description" name="description" rows="6"><?= htmlspecialchars($service->translations['description'], ENT_QUOTES, 'UTF-8') ?></textarea>

        <fieldset class="backoffice-fieldset">
            <legend>Contact</legend>

            <label for="bo-phone">Téléphone</label>
            <input type="text" id="bo-phone" name="phone" value="<?= htmlspecialchars($service->contact['phone'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="bo-mobile">Mobile</label>
            <input type="text" id="bo-mobile" name="mobile" value="<?= htmlspecialchars($service->contact['mobile'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="bo-email">Email</label>
            <input type="email" id="bo-email" name="email" value="<?= htmlspecialchars($service->contact['email'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="bo-website">Site web</label>
            <input type="url" id="bo-website" name="website" value="<?= htmlspecialchars($service->contact['website'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="bo-facebook">Facebook</label>
            <input type="url" id="bo-facebook" name="facebook" value="<?= htmlspecialchars($service->contact['facebook'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </fieldset>

        <fieldset class="backoffice-fieldset">
            <legend>Adresse</legend>

            <label for="bo-address">Adresse</label>
            <input type="text" id="bo-address" name="address" value="<?= htmlspecialchars($service->contact['address'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="bo-postal">Code postal</label>
            <input type="text" id="bo-postal" name="postal_code" value="<?= htmlspecialchars($service->contact['cp'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="bo-city">Ville</label>
            <input type="text" id="bo-city" name="city" value="<?= htmlspecialchars($service->contact['ville'], ENT_QUOTES, 'UTF-8') ?>">
        </fieldset>

        <fieldset class="backoffice-fieldset">
            <legend>Catégories</legend>
            <div class="backoffice-checkbox-grid">
                <?php foreach ($allCategories as $cat): ?>
                    <label>
                        <input type="checkbox" name="categories[]" value="<?= (int) $cat['id'] ?>"
                            <?= in_array((int) $cat['id'], $categoryIds, true) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <fieldset class="backoffice-fieldset">
            <legend>Photos</legend>

            <label for="bo-cover">Photo de couverture</label>
            <input type="file" id="bo-cover" name="cover_file" accept="image/jpeg,image/png,image/webp">
            <?php if (!empty($service->imageUrl)): ?>
                <div class="backoffice-gallery-preview">
                    <img src="<?= htmlspecialchars($service->imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Couverture actuelle">
                </div>
            <?php endif; ?>

            <label for="bo-gallery">Ajouter des photos à la galerie</label>
            <input type="file" id="bo-gallery" name="gallery_files[]" accept="image/jpeg,image/png,image/webp" multiple>
            <?php if (!empty($service->gallery)): ?>
                <div class="backoffice-gallery-preview">
                    <?php foreach ($service->gallery as $photo): ?>
                        <img src="<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>" alt="Photo de la galerie">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </fieldset>

        <button type="submit" class="button button-primary">Enregistrer</button>
    </form>
</main>
