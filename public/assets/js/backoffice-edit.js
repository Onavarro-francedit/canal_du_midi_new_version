// public/assets/js/backoffice-edit.js
document.addEventListener('DOMContentLoaded', () => {
    const alertBox = document.getElementById('bo-ajax-alert');

    function openModal(id) {
        document.getElementById(id)?.classList.add('is-active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(overlay) {
        overlay.classList.remove('is-active');
        document.body.style.overflow = '';
        const error = overlay.querySelector('.ficha-modal-error');
        if (error) error.hidden = true;
    }

    function showModalError(errorBox, message) {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.hidden = false;
        // 'center' et pas 'nearest' : le titre collant <h2>Photos</h2> recouvre le haut
        // de la zone défilante, et 'nearest' laisse le message caché dessous (BUG-018).
        errorBox.scrollIntoView({ block: 'center' });
    }

    // ── Confirmation générique (remplace window.confirm par un modal cohérent avec le reste de l'UI).
    const confirmModal = document.getElementById('modal-confirm');
    const confirmMessageEl = document.getElementById('modal-confirm-message');
    function confirmDialog(message) {
        if (!confirmModal || !confirmMessageEl) return Promise.resolve(window.confirm(message));
        return new Promise((resolve) => {
            confirmMessageEl.textContent = message;
            openModal('modal-confirm');

            const okBtn = confirmModal.querySelector('[data-confirm="ok"]');
            const cancelBtn = confirmModal.querySelector('[data-confirm="cancel"]');

            function settle(result) {
                closeModal(confirmModal);
                okBtn.removeEventListener('click', onOk);
                cancelBtn.removeEventListener('click', onCancel);
                confirmModal.removeEventListener('click', onOverlayClick);
                resolve(result);
            }
            function onOk() { settle(true); }
            function onCancel() { settle(false); }
            function onOverlayClick(e) { if (e.target === confirmModal) settle(false); }

            okBtn.addEventListener('click', onOk);
            cancelBtn.addEventListener('click', onCancel);
            confirmModal.addEventListener('click', onOverlayClick);
        });
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function showSavedAlert() {
        if (!alertBox) return;
        alertBox.hidden = false;
        clearTimeout(showSavedAlert._t);
        showSavedAlert._t = setTimeout(() => { alertBox.hidden = true; }, 3000);
    }

    document.querySelectorAll('.ficha-edit-btn').forEach((btn) => {
        btn.addEventListener('click', () => openModal(btn.dataset.modal));
    });

    document.querySelectorAll('.modal-overlay').forEach((overlay) => {
        overlay.querySelector('.ficha-modal-cancel')?.addEventListener('click', () => closeModal(overlay));
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal(overlay);
        });
    });

    function updateFichaFromForm(form) {
        form.querySelectorAll('input[name], textarea[name]').forEach((field) => {
            if (field.type === 'checkbox' || field.type === 'file') return;
            const targets = document.querySelectorAll(`[data-ficha-field="${field.name}"]`);
            if (!targets.length) return;

            const value = field.value.trim();
            targets.forEach((target) => {
                if (field.tagName === 'TEXTAREA') {
                    target.innerHTML = value ? escapeHtml(value).replace(/\n/g, '<br>') : '—';
                } else {
                    target.textContent = value || '—';
                }
            });
        });

        if (form.dataset.block === 'categories') {
            const checked = form.querySelectorAll('input[name="categories[]"]:checked');
            const target = document.getElementById('ficha-categories');
            target.innerHTML = '';
            if (checked.length === 0) {
                target.innerHTML = '<p>—</p>';
            } else {
                checked.forEach((cb) => {
                    const tag = document.createElement('div');
                    tag.className = 'category-tag';
                    tag.innerHTML = '<i class="bi bi-check2"></i> ' + escapeHtml(cb.parentElement.textContent.trim());
                    target.appendChild(tag);
                });
            }
        }
    }

    const heroPreview = document.getElementById('bo-hero-preview');
    function setHeroPreview(url) {
        if (!heroPreview) return;
        heroPreview.style.backgroundImage = url ? `url('${url}')` : '';
    }

    // ── Sélection des photos affichées sur la fiche (carrousel = plusieurs, dans l'ordre de sélection ; image unique = une seule).
    function getHeroModeValue() {
        return document.querySelector('input[name="hero_mode"]:checked')?.value || 'carousel';
    }

    // La checkbox cochée devient la dernière du bloc "sélectionné" : sa position dans le DOM
    // sert d'ordre au moment du submit (syncGalleryOrder lit déjà cet ordre-là).
    function moveToSelectionEnd(label, picker) {
        const checkedOthers = Array.from(picker.querySelectorAll('.ficha-cover-option')).filter((opt) => {
            if (opt === label) return false;
            return opt.querySelector('.ficha-cover-checkbox')?.checked;
        });
        if (checkedOthers.length) {
            picker.insertBefore(label, checkedOthers[checkedOthers.length - 1].nextSibling);
        } else {
            picker.insertBefore(label, picker.firstChild);
        }
    }

    function updateCoverOrderBadges() {
        const picker = document.querySelector('.ficha-cover-picker');
        if (!picker) return;
        const carousel = getHeroModeValue() === 'carousel';
        let n = 0;
        picker.querySelectorAll('.ficha-cover-option').forEach((option) => {
            const cb = option.querySelector('.ficha-cover-checkbox');
            const badge = option.querySelector('.ficha-cover-order');
            if (!cb || !badge) return;
            badge.textContent = (carousel && cb.checked) ? String(++n) : '';
        });
    }

    // La portada (cover_source / imageUrl) est toujours la première photo cochée.
    function syncCoverSource() {
        const hidden = document.getElementById('bo-cover-source');
        const picker = document.querySelector('.ficha-cover-picker');
        if (!hidden) return;
        const firstChecked = picker?.querySelector('.ficha-cover-checkbox:checked');
        if (!firstChecked) return;
        // La valeur envoyée est l'URL réelle ou, pour une photo pas encore enregistrée, le
        // jeton "new:N" (résolu côté serveur) — l'aperçu, lui, utilise toujours l'image déjà
        // affichée dans la vignette (blob local ou URL réelle), jamais cette valeur brute.
        hidden.value = firstChecked.value;
        const previewSrc = firstChecked.closest('.ficha-cover-option')?.querySelector('img')?.src;
        if (previewSrc) setHeroPreview(previewSrc);
    }

    document.addEventListener('change', (e) => {
        const cb = e.target;
        if (!cb.matches('.ficha-cover-checkbox')) return;
        const picker = cb.closest('.ficha-cover-picker');
        const label = cb.closest('.ficha-cover-option');
        if (!picker || !label) return;

        if (getHeroModeValue() === 'single') {
            if (cb.checked) {
                picker.querySelectorAll('.ficha-cover-checkbox').forEach((other) => {
                    if (other !== cb) other.checked = false;
                });
            } else {
                cb.checked = true; // image unique : toujours une seule photo cochée, jamais zéro
            }
        } else if (cb.checked) {
            moveToSelectionEnd(label, picker);
        } else if (!picker.querySelector('.ficha-cover-checkbox:checked')) {
            cb.checked = true; // au moins une photo doit rester affichée
        }

        updateCoverOrderBadges();
        syncCoverSource();
    });

    document.querySelectorAll('input[name="hero_mode"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            const picker = document.querySelector('.ficha-cover-picker');
            const hint = document.getElementById('bo-cover-hint');
            if (hint) {
                hint.textContent = radio.value === 'single'
                    ? '(cliquez une photo pour la choisir)'
                    : '(cliquez les photos à afficher, dans l’ordre voulu)';
            }
            if (picker && radio.value === 'single') {
                Array.from(picker.querySelectorAll('.ficha-cover-checkbox:checked')).slice(1).forEach((cb) => {
                    cb.checked = false;
                });
            }
            updateCoverOrderBadges();
            syncCoverSource();
        });
    });

    updateCoverOrderBadges();
    syncCoverSource();

    // ── Repeint des photos (portada + galerie + hero) après upload/borrado/réordonnancement.
    // Construit via DOM API (pas d'innerHTML avec des URLs de BD non échappées).
    function renderPhotos(imageUrl, gallery) {
        gallery = Array.isArray(gallery) ? gallery : [];

        // Hero de la ficha + preview du modal
        const hero = document.getElementById('ficha-hero');
        if (hero) hero.style.backgroundImage = imageUrl ? `url('${imageUrl}')` : '';
        setHeroPreview(imageUrl);

        // Galerie visible sous la ficha (hors <form>)
        const galleryPreview = document.getElementById('ficha-gallery-preview');
        if (galleryPreview) {
            galleryPreview.innerHTML = '';
            if (gallery.length === 0) {
                const empty = document.createElement('p');
                empty.textContent = 'Aucune photo pour le moment.';
                galleryPreview.appendChild(empty);
            } else {
                gallery.forEach((url) => {
                    const item = document.createElement('div');
                    item.className = 'gallery-item';
                    const img = document.createElement('img');
                    img.src = url;
                    img.alt = 'Photo de la galerie';
                    const del = document.createElement('button');
                    del.type = 'button';
                    del.className = 'ficha-photo-delete';
                    del.dataset.photoUrl = url;
                    del.setAttribute('aria-label', 'Supprimer cette photo');
                    del.textContent = '×';
                    item.appendChild(img);
                    item.appendChild(del);
                    galleryPreview.appendChild(item);
                });
            }
        }

        // Picker de couverture dans le modal Photos
        const picker = document.querySelector('.ficha-cover-picker');
        if (picker) {
            // La tuile "Ajouter des photos" (.ficha-cover-add) garde ses écouteurs (dropzone) :
            // on ne reconstruit que les vignettes de photos, insérées juste avant elle.
            const addTile = picker.querySelector('.ficha-cover-add');

            // Préserve la sélection en cours (carrousel = plusieurs photos) avant de tout reconstruire.
            // Les jetons "new:N" des vignettes en attente ne correspondront à aucune URL réelle ci-dessous :
            // c'est voulu, leur statut coché a déjà été appliqué côté serveur (gallery_order/cover_source).
            const priorSelected = Array.from(picker.querySelectorAll('.ficha-cover-checkbox:checked')).map((cb) => cb.value);

            picker.querySelectorAll('.ficha-cover-option, .ficha-cover-empty').forEach((el) => el.remove());
            const photos = [imageUrl, ...gallery].filter((url, i, arr) => url && arr.indexOf(url) === i);

            let selected = priorSelected.filter((url) => photos.includes(url));
            if (imageUrl && !selected.includes(imageUrl)) selected.unshift(imageUrl);
            if (selected.length === 0) selected = photos.slice(0, 1);

            if (photos.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'ficha-cover-empty';
                empty.textContent = 'Aucune photo pour le moment.';
                picker.insertBefore(empty, addTile);
            }
            photos.forEach((url, i) => {
                const label = document.createElement('label');
                label.className = 'ficha-cover-option';
                label.draggable = true;
                label.dataset.photoUrl = url;

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'ficha-cover-checkbox';
                checkbox.value = url;
                checkbox.checked = selected.includes(url);

                const img = document.createElement('img');
                img.src = url;
                img.alt = `Photo ${i + 1}`;
                img.draggable = false;

                const check = document.createElement('span');
                check.className = 'ficha-cover-check';
                check.innerHTML = '<i class="bi bi-check-circle-fill"></i>';

                const order = document.createElement('span');
                order.className = 'ficha-cover-order';

                const del = document.createElement('button');
                del.type = 'button';
                del.className = 'ficha-photo-delete';
                del.dataset.photoUrl = url;
                del.setAttribute('aria-label', 'Supprimer cette photo');
                del.textContent = '×';

                label.append(checkbox, img, check, order, del);
                picker.insertBefore(label, addTile);
            });
            bindGalleryDragAndDrop();
            updateCoverOrderBadges();
            syncCoverSource();
        }
    }

    // Retire un fichier précis de l'<input type="file"> (avant l'envoi — aucun appel réseau ici).
    function removePendingFile(input, file) {
        input._pendingFiles = (input._pendingFiles || []).filter((f) => f !== file);
        syncPendingInputFiles(input);
        renderDropzonePreview(input, input._pendingFiles);
    }

    function syncPendingInputFiles(input) {
        const dt = new DataTransfer();
        (input._pendingFiles || []).forEach((f) => dt.items.add(f));
        input.files = dt.files;
    }

    // Vignettes des photos pas encore envoyées : toujours dans la grille, avant la tuile
    // "Ajouter" (jamais après), avec une checkbox réelle au même titre que les photos déjà
    // enregistrées — value="new:<index dans input.files>". Le fichier n'a pas encore d'URL
    // serveur, donc gallery_order[]/cover_source transportent ce jeton tel quel ; c'est
    // BackofficeController qui le résout en chemin réel une fois le fichier stocké (voir
    // case 'photos'). L'ordre et le statut coché suivent donc exactement le même mécanisme
    // que pour une photo déjà sauvegardée.
    function renderDropzonePreview(input, files) {
        const picker = document.querySelector('.ficha-cover-picker');
        const addTile = picker?.querySelector('.ficha-cover-add');
        if (!picker || !addTile) return;

        // Préserve, par identité de fichier (pas par index — un retrait décale les index),
        // la sélection en cours des vignettes en attente avant de tout reconstruire.
        const priorFiles = new Set();
        const priorChecked = new Set();
        picker.querySelectorAll('.ficha-cover-pending').forEach((tile) => {
            if (!tile._file) return;
            priorFiles.add(tile._file);
            if (tile.querySelector('.ficha-cover-checkbox')?.checked) priorChecked.add(tile._file);
        });

        picker.querySelectorAll('.ficha-cover-pending').forEach((el) => el.remove());

        Array.from(files).forEach((file, index) => {
            if (!file.type.startsWith('image/')) return;

            const tile = document.createElement('label');
            tile.className = 'ficha-cover-option ficha-cover-pending';
            tile.draggable = true;
            tile.dataset.photoUrl = `new:${index}`;
            tile._file = file;

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'ficha-cover-checkbox';
            checkbox.value = `new:${index}`;
            checkbox.checked = priorFiles.has(file) ? priorChecked.has(file) : true;

            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.alt = file.name;
            img.draggable = false;

            const check = document.createElement('span');
            check.className = 'ficha-cover-check';
            check.innerHTML = '<i class="bi bi-check-circle-fill"></i>';

            const order = document.createElement('span');
            order.className = 'ficha-cover-order';

            const badge = document.createElement('span');
            badge.className = 'ficha-cover-pending-badge';
            badge.textContent = 'Nouvelle';

            const del = document.createElement('button');
            del.type = 'button';
            del.className = 'ficha-photo-delete';
            del.setAttribute('aria-label', `Retirer ${file.name}`);
            del.textContent = '×';
            del.addEventListener('click', (e) => {
                e.preventDefault(); // ne pas déclencher le "change" de la checkbox du <label>
                removePendingFile(input, file);
            });

            tile.append(checkbox, img, check, order, badge, del);
            picker.insertBefore(tile, addTile);
            bindDragHandlers(tile, picker); // seulement cette vignette : les autres ont déjà leurs écouteurs
        });

        updateCoverOrderBadges();
        syncCoverSource();
    }

    // ── Validation cliente des fichiers (UX uniquement — le serveur revalide tout : finfo, 5Mo, MIME allowlist).
    const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;
    const ALLOWED_UPLOAD_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    function validateFiles(input) {
        for (const file of input.files) {
            if (file.size > MAX_UPLOAD_BYTES) {
                return `« ${file.name} » dépasse 5 Mo.`;
            }
            if (!ALLOWED_UPLOAD_TYPES.includes(file.type)) {
                return `« ${file.name} » : format non pris en charge (JPG, PNG ou WEBP).`;
            }
        }
        return '';
    }

    document.querySelectorAll('.ficha-dropzone').forEach((zone) => {
        const input = document.getElementById(zone.dataset.input);
        if (!input) return;
        const form = zone.closest('form');
        const errorBox = form?.querySelector('.ficha-modal-error');

        // Le sélecteur de fichiers natif REMPLACE input.files à chaque sélection (aucune
        // fusion automatique) : on accumule donc nous-mêmes dans input._pendingFiles pour
        // qu'une deuxième sélection s'ajoute à la première au lieu de l'écraser.
        function addFiles(newFiles) {
            const additions = Array.from(newFiles).filter((f) => f.type.startsWith('image/'));
            const before = (input._pendingFiles || []).slice();
            input._pendingFiles = before.concat(additions);
            syncPendingInputFiles(input);

            const error = validateFiles(input);
            if (error) {
                input._pendingFiles = before;
                syncPendingInputFiles(input);
                showModalError(errorBox, error);
                return;
            }
            renderDropzonePreview(input, input._pendingFiles);
        }

        zone.addEventListener('click', () => input.click());
        input.addEventListener('change', () => addFiles(input.files));

        ['dragenter', 'dragover'].forEach((type) => {
            zone.addEventListener(type, (e) => {
                e.preventDefault();
                zone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach((type) => {
            zone.addEventListener(type, (e) => {
                e.preventDefault();
                zone.classList.remove('is-dragover');
            });
        });

        zone.addEventListener('drop', (e) => {
            const files = e.dataTransfer?.files;
            if (!files || !files.length) return;
            addFiles(files);
        });
    });

    // ── Drag & drop natif pour réordonner la galerie (sur le picker du modal, pas sur la preview hors <form>).
    // `dragged` est partagé au niveau module (pas par appel) : renderDropzonePreview ajoute des
    // vignettes une à une sans recréer les existantes, il ne faut donc jamais réattacher ces
    // écouteurs à une vignette qui les a déjà — cf. bindDragHandlers, appelée une seule fois par élément.
    let draggedOption = null;
    function bindDragHandlers(option, picker) {
        option.addEventListener('dragstart', () => {
            draggedOption = option;
            option.classList.add('is-dragging');
        });
        option.addEventListener('dragend', () => {
            option.classList.remove('is-dragging');
            draggedOption = null;
        });
        option.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (!draggedOption || draggedOption === option) return;
            const rect = option.getBoundingClientRect();
            const before = (e.clientX - rect.left) < rect.width / 2;
            picker.insertBefore(draggedOption, before ? option : option.nextSibling);
        });
    }

    function bindGalleryDragAndDrop() {
        const picker = document.querySelector('.ficha-cover-picker');
        if (!picker) return;
        picker.querySelectorAll('.ficha-cover-option').forEach((option) => bindDragHandlers(option, picker));
    }
    bindGalleryDragAndDrop();

    // Écrit l'ordre courant du picker dans des <input type="hidden" name="gallery_order[]"> avant submit.
    function syncGalleryOrder(form) {
        const orderContainer = form.querySelector('#bo-gallery-order');
        if (!orderContainer) return;
        orderContainer.innerHTML = '';
        form.querySelectorAll('.ficha-cover-option').forEach((option) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'gallery_order[]';
            input.value = option.dataset.photoUrl || '';
            orderContainer.appendChild(input);
        });
    }

    // ── Suppression d'une photo (cover ou galerie), déléguée car les vignettes sont repeintes dynamiquement.
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.ficha-photo-delete');
        if (!btn) return;
        if (btn.closest('.ficha-cover-pending')) return; // géré localement dans renderDropzonePreview, pas d'appel serveur
        e.preventDefault(); // le bouton est dans un <label> : ne pas basculer sa checkbox au passage

        if (!(await confirmDialog('Supprimer définitivement cette photo ?'))) return;

        const modal = document.getElementById('modal-photos');
        const csrfInput = modal?.querySelector('input[name="csrf"]');
        const errorBox = modal?.querySelector('.ficha-modal-error');
        if (!csrfInput) return;

        btn.disabled = true;

        const formData = new FormData();
        formData.append('block', 'delete_photo');
        formData.append('csrf', csrfInput.value);
        formData.append('photo_url', btn.dataset.photoUrl || '');

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json();

            if (!data.success) {
                showModalError(errorBox, data.error || 'Une erreur est survenue.');
                btn.disabled = false;
                return;
            }

            renderPhotos(data.imageUrl, data.gallery);
            showSavedAlert();
        } catch (err) {
            showModalError(errorBox, 'Erreur réseau, réessayez.');
            btn.disabled = false;
        }
    });

    document.querySelectorAll('.ficha-modal-form').forEach((form) => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const overlay = form.closest('.modal-overlay');
            const errorBox = form.querySelector('.ficha-modal-error');
            const submitBtn = form.querySelector('button[type="submit"]');
            const submitBtnDefaultText = submitBtn.textContent;

            if (form.dataset.block === 'photos') {
                const picker = form.querySelector('.ficha-cover-picker');
                const hasPhotos = picker && picker.querySelector('.ficha-cover-option');
                if (hasPhotos && !picker.querySelector('.ficha-cover-checkbox:checked')) {
                    showModalError(errorBox, 'Sélectionnez au moins une photo à afficher.');
                    return;
                }
                syncGalleryOrder(form);
            }

            const formData = new FormData(form);
            formData.append('block', form.dataset.block);
            submitBtn.disabled = true;
            submitBtn.classList.add('is-loading');
            submitBtn.textContent = 'Envoi en cours…';

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await response.json();

                if (!data.success) {
                    showModalError(errorBox, data.error || 'Une erreur est survenue.');
                    return;
                }

                if (form.dataset.block === 'photos') {
                    renderPhotos(data.imageUrl, data.gallery);
                    const galleryInput = document.getElementById('bo-gallery');
                    if (galleryInput) {
                        galleryInput.value = '';
                        galleryInput._pendingFiles = [];
                    }
                } else {
                    updateFichaFromForm(form);
                }

                closeModal(overlay);
                showSavedAlert();
            } catch (err) {
                showModalError(errorBox, 'Erreur réseau, réessayez.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.classList.remove('is-loading');
                submitBtn.textContent = submitBtnDefaultText;
            }
        });
    });
});
