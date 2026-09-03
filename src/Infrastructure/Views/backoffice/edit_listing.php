<?php
// src/Infrastructure/Views/backoffice/edit_listing.php
use App\Infrastructure\Services\Csrf;

$categoryIds = array_column($service->categories, 'id');
$csrfToken = Csrf::token();
$fullAddress = trim((string) ($service->contact['address_raw'] ?? '')) ?: 'Occitanie, France';
?>
<div style="width:100%; min-width: 30rem; min-height:4rem;  overflow-x:auto;">
    <ul style="display:flex; justify-content:space-between; align-items:center; padding: 0 2rem;">
        <li
            id="item_edit_listing"
            class="ul_item_active"
            style="margin-right:2rem; list-style:none; cursor:pointer;"
        >
            <p style="white-space: nowrap; ">Modifier ma fiche</p>
        </li>
        <li
            id="item_edit_account"
            style="margin-right:2rem; list-style:none; cursor:pointer;"
        >
            <p style="white-space: nowrap;">Modifier mon compte</p>
        </li>
        <li
            id="item_logout"
            style="margin-right:15rem; list-style:none; cursor:pointer;"
        >
            <p style="white-space: nowrap;">Me déconnecter</p>
        </li>
    </ul>

</div>

<div id="bo-ajax-alert" class="backoffice-alert backoffice-alert--success ficha-floating-alert" hidden>Modifications enregistrées.</div>

<!-- Ficha au design identique à /fiche/{slug} — chaque bloc éditable porte son icône crayon -->
<main class="service-page">
    <section class="service-hero" id="ficha-hero" style="background-image: url('<?= htmlspecialchars($service->imageUrl, ENT_QUOTES, 'UTF-8') ?>');">
        <button type="button" class="ficha-edit-btn ficha-edit-btn--hero" data-modal="modal-photos" title="Modifier la photo de couverture"><i class="bi bi-pencil"></i></button>
        <div class="container">
            <div class="service-hero-content">
                <h1 data-ficha-field="title"><?= htmlspecialchars($service->translations['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="service-location-row">
                    <div class="service-location">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span data-ficha-field="address"><?= htmlspecialchars($fullAddress, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container service-grid">
        <div class="service-main-content">
            <section class="section-card section-card-intro ficha-editable">
                <button type="button" class="ficha-edit-btn" data-modal="modal-info" title="Modifier"><i class="bi bi-pencil"></i></button>
                <div class="section-heading-inline">
                    <span class="section-kicker">Présentation</span>
                    <h2 data-ficha-field="title"><?= htmlspecialchars($service->translations['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                </div>
                <div class="description-text" data-ficha-field="description"><?= nl2br(htmlspecialchars($service->translations['description'], ENT_QUOTES, 'UTF-8')) ?></div>
            </section>

            <section class="section-card ficha-editable">
                <button type="button" class="ficha-edit-btn" data-modal="modal-categories" title="Modifier"><i class="bi bi-pencil"></i></button>
                <div class="section-heading-inline">
                    <span class="section-kicker">Activités</span>
                    <h3>Catégories &amp; Services</h3>
                </div>
                <div class="categories-grid" id="ficha-categories">
                    <?php if (empty($service->categories)): ?>
                        <p>—</p>
                    <?php else: ?>
                        <?php foreach ($service->categories as $cat): ?>
                            <div class="category-tag"><i class="bi bi-check2"></i> <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="section-card ficha-editable">
                <button type="button" class="ficha-edit-btn" data-modal="modal-photos" title="Modifier"><i class="bi bi-pencil"></i></button>
                <div class="section-heading-inline">
                    <span class="section-kicker">Ambiance</span>
                    <h3>Galerie Photos</h3>
                </div>
                <div class="masonry-gallery" id="ficha-gallery-preview">
                    <?php foreach ($service->gallery as $photo): ?>
                        <div class="gallery-item">
                            <img src="<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>" alt="Photo de la galerie">
                            <button type="button" class="ficha-photo-delete" data-photo-url="<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>" aria-label="Supprimer cette photo">&times;</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="section-card map-section ficha-editable">
                <button type="button" class="ficha-edit-btn" data-modal="modal-address" title="Modifier"><i class="bi bi-pencil"></i></button>
                <div class="location-hero">
                    <div class="section-heading-inline location-heading">
                        <span class="section-kicker">Accès</span>
                        <h3>Localisation</h3>
                    </div>
                </div>
                <div class="location-address-card">
                    <span class="location-address-label">Adresse de l'établissement</span>
                    <p class="address-footer">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span data-ficha-field="address"><?= htmlspecialchars($service->contact['address_raw'], ENT_QUOTES, 'UTF-8') ?></span>,
                        <span data-ficha-field="postal_code"><?= htmlspecialchars($service->contact['cp'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span data-ficha-field="city"><?= htmlspecialchars($service->contact['ville'], ENT_QUOTES, 'UTF-8') ?></span>
                    </p>
                </div>
            </section>
        </div>

        <aside class="service-sidebar">
            <div class="contact-card ficha-editable">
                <button type="button" class="ficha-edit-btn" data-modal="modal-contact" title="Modifier"><i class="bi bi-pencil"></i></button>
                <span class="section-kicker">Informations utiles</span>
                <h3 class="sidebar-card-title">Coordonnées</h3>
                <p class="sidebar-card-copy">Contactez directement l'établissement pour toute demande d'information ou de réservation.</p>
                <ul class="contact-list">
                    <li>
                        <i class="bi bi-telephone"></i>
                        <div><strong>Téléphone</strong><span data-ficha-field="phone"><?= htmlspecialchars($service->contact['phone'], ENT_QUOTES, 'UTF-8') ?: '—' ?></span></div>
                    </li>
                    <li>
                        <i class="bi bi-phone"></i>
                        <div><strong>Mobile</strong><span data-ficha-field="mobile"><?= htmlspecialchars($service->contact['mobile'], ENT_QUOTES, 'UTF-8') ?: '—' ?></span></div>
                    </li>
                    <li>
                        <i class="bi bi-envelope"></i>
                        <div><strong>Email</strong><span data-ficha-field="email"><?= htmlspecialchars($service->contact['email'], ENT_QUOTES, 'UTF-8') ?: '—' ?></span></div>
                    </li>
                    <li>
                        <i class="bi bi-globe"></i>
                        <div><strong>Site web</strong><span data-ficha-field="website"><?= htmlspecialchars($service->contact['website'], ENT_QUOTES, 'UTF-8') ?: '—' ?></span></div>
                    </li>
                    <li>
                        <i class="bi bi-facebook"></i>
                        <div><strong>Facebook</strong><span data-ficha-field="facebook"><?= htmlspecialchars($service->contact['facebook'] ?? '', ENT_QUOTES, 'UTF-8') ?: '—' ?></span></div>
                    </li>
                </ul>
            </div>
        </aside>
    </div>
</main>

<!-- Modal: Titre & Description -->
<div class="modal-overlay" id="modal-info">
    <div class="modal-card ficha-modal-card">
        <form class="ficha-modal-form" data-block="info">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <h2>Titre &amp; description</h2>
            <p class="ficha-modal-error" hidden></p>

            <label for="bo-title">Titre</label>
            <input type="text" id="bo-title" name="title" required value="<?= htmlspecialchars($service->translations['title'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="bo-description">Description</label>
            <textarea id="bo-description" name="description" rows="6"><?= htmlspecialchars($service->translations['description'], ENT_QUOTES, 'UTF-8') ?></textarea>

            <div class="modal-actions">
                <button type="button" class="button button-ghost ficha-modal-cancel">Annuler</button>
                <button type="submit" class="button button-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Contact -->
<div class="modal-overlay" id="modal-contact">
    <div class="modal-card ficha-modal-card">
        <form class="ficha-modal-form" data-block="contact">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <h2>Contact</h2>
            <p class="ficha-modal-error" hidden></p>

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

            <div class="modal-actions">
                <button type="button" class="button button-ghost ficha-modal-cancel">Annuler</button>
                <button type="submit" class="button button-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Adresse -->
<div class="modal-overlay" id="modal-address">
    <div class="modal-card ficha-modal-card">
        <form class="ficha-modal-form" data-block="address">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <h2>Adresse</h2>
            <p class="ficha-modal-error" hidden></p>

            <label for="bo-address">Adresse</label>
            <input type="text" id="bo-address" name="address" value="<?= htmlspecialchars($service->contact['address_raw'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="bo-postal">Code postal</label>
            <input type="text" id="bo-postal" name="postal_code" value="<?= htmlspecialchars($service->contact['cp'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="bo-city">Ville</label>
            <input type="text" id="bo-city" name="city" value="<?= htmlspecialchars($service->contact['ville'], ENT_QUOTES, 'UTF-8') ?>">

            <label>Position sur la carte <span class="ficha-map-hint">(glissez le repère pour l'ajuster)</span></label>
            <div id="bo-address-map" class="ficha-address-map" data-lat="<?= (float) $service->lat ?>" data-lng="<?= (float) $service->lng ?>"></div>
            <input type="hidden" id="bo-lat" name="lat" value="<?= (float) $service->lat ?>">
            <input type="hidden" id="bo-lng" name="lng" value="<?= (float) $service->lng ?>">

            <div class="modal-actions">
                <button type="button" class="button button-ghost ficha-modal-cancel">Annuler</button>
                <button type="submit" class="button button-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Catégories -->
<div class="modal-overlay" id="modal-categories">
    <div class="modal-card ficha-modal-card">
        <form class="ficha-modal-form" data-block="categories">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <h2>Catégories</h2>
            <p class="ficha-modal-error" hidden></p>

            <div class="backoffice-checkbox-grid">
                <?php foreach ($allCategories as $cat): ?>
                    <label>
                        <input type="checkbox" name="categories[]" value="<?= (int) $cat['id'] ?>"
                            <?= in_array((int) $cat['id'], $categoryIds, true) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="modal-actions">
                <button type="button" class="button button-ghost ficha-modal-cancel">Annuler</button>
                <button type="submit" class="button button-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Photos -->
<?php
$existingPhotos = array_values(array_unique(array_filter(array_merge([$service->imageUrl], $service->gallery))));
// Reflète la sélection actuellement affichée dans le carrousel (cf. $heroSlides dans
// service_detail.php : cover + galerie, sans limite) pour que le picker s'ouvre déjà
// cochée sur ce qui est réellement visible sur la fiche publique.
$heroSlideDefaults = $service->heroMode === 'single'
    ? array_filter([$service->imageUrl])
    : $existingPhotos;
?>
<div class="modal-overlay" id="modal-photos">
    <div class="modal-card ficha-modal-card ficha-modal-card--wide">
        <form class="ficha-modal-form" data-block="photos" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <h2>Images</h2>
            <p class="ficha-modal-error" hidden></p>

            <label>Affichage sur la fiche</label>
            <div class="ficha-hero-mode-picker">
                <label class="ficha-radio-pill">
                    <input type="radio" name="hero_mode" value="carousel" <?= $service->heroMode !== 'single' ? 'checked' : '' ?>>
                    <i class="bi bi-images"></i> Carrousel (plusieurs photos)
                </label>
                <label class="ficha-radio-pill">
                    <input type="radio" name="hero_mode" value="single" <?= $service->heroMode === 'single' ? 'checked' : '' ?>>
                    <i class="bi bi-image"></i> Image unique
                </label>
            </div>

            <div class="ficha-hero-preview" id="bo-hero-preview" style="background-image: url('<?= htmlspecialchars($service->imageUrl, ENT_QUOTES, 'UTF-8') ?>');">
                <span id="bo-hero-preview-title"><?= htmlspecialchars($service->translations['title'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <!-- Toujours présent, même sans photo au chargement : renderPhotos() (JS) en a besoin après un premier upload. -->
            <input type="hidden" name="cover_source" id="bo-cover-source" value="<?= htmlspecialchars($service->imageUrl, ENT_QUOTES, 'UTF-8') ?>">

            <label>
                Photos affichées
                <span class="ficha-map-hint" id="bo-cover-hint">
                    <?= $service->heroMode === 'single'
                        ? '(cliquez une photo pour la choisir)'
                        : '(cliquez les photos à afficher, dans l’ordre voulu)' ?>
                </span>
            </label>
            <div class="ficha-cover-picker">
                <?php foreach ($existingPhotos as $i => $photoUrl): ?>
                    <label class="ficha-cover-option" draggable="true" data-photo-url="<?= htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="checkbox" class="ficha-cover-checkbox" value="<?= htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') ?>"
                            <?= in_array($photoUrl, $heroSlideDefaults, true) ? 'checked' : '' ?>>
                        <img src="<?= htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Photo <?= $i + 1 ?>" draggable="false">
                        <span class="ficha-cover-check"><i class="bi bi-check-circle-fill"></i></span>
                        <span class="ficha-cover-order"></span>
                        <button type="button" class="ficha-photo-delete" data-photo-url="<?= htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Supprimer cette photo">&times;</button>
                    </label>
                <?php endforeach; ?>
                <!-- Tuile "ajouter", toujours en dernier : renderPhotos()/renderDropzonePreview() l'utilisent
                     comme repère et n'insèrent jamais rien après elle. -->
                <div class="ficha-dropzone ficha-cover-add" data-input="bo-gallery">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <span>Ajouter des photos</span>
                    <input type="file" id="bo-gallery" name="gallery_files[]" accept="image/jpeg,image/png,image/webp" multiple hidden>
                </div>
            </div>

            <div id="bo-gallery-order" hidden></div>

            <div class="modal-actions">
                <button type="button" class="button button-ghost ficha-modal-cancel">Annuler</button>
                <button type="submit" class="button button-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal : confirmation générique (suppression, etc.) -->
<div class="modal-overlay" id="modal-confirm">
    <div class="modal-card">
        <p id="modal-confirm-message"></p>
        <div class="modal-actions">
            <button type="button" class="button button-ghost" data-confirm="cancel">Annuler</button>
            <button type="button" class="button button-primary" data-confirm="ok">Supprimer</button>
        </div>
    </div>
</div>
