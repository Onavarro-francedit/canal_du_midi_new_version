# Diseño: mejoras funcionales al modal "Photos" del backoffice

**Fecha:** 2026-09-03
**Contexto:** modal `#modal-photos` en `src/Infrastructure/Views/backoffice/edit_listing.php`, usado desde `http://localhost/canal_du_midi/fr/backoffice?id=<listing>`.

## Problema actual

El modal solo permite **añadir** fotos (portada nueva y galería). No existe forma de:
- Eliminar una foto ya subida (hay que editar la BD a mano).
- Reordenar la galería (el orden de subida determina el orden del carrusel público).
- Saber si una subida fue rechazada por tamaño/tipo antes de que el servidor responda, o ver que algo está en curso.

Confirmado en código: `BackofficeController.php` (líneas ~134-173 y ~293-325) solo hace `array_merge` sobre `gallery`, nunca resta elementos. No existe ninguna acción de borrado ni de reordenado.

## Alcance de este diseño

Tres mejoras, todas dentro del modal Photos existente:

1. Borrado de fotos de la galería/portada.
2. Reordenado de la galería por drag & drop.
3. Validación cliente + estado de carga en las subidas.

Fuera de alcance (explícitamente descartado, se puede añadir después si hace falta): recorte/edición de imagen, texto alternativo por foto, subida con barra de progreso real (XHR), cola de subida asíncrona.

## 1. Borrado de fotos

**Backend**
- Nueva acción `delete_photo` en `BackofficeController` (mismo patrón que las acciones existentes del formulario `photos`): recibe `photo_url` + `csrf`, valida que la URL pertenezca al `gallery` (o sea la `imageUrl`) del listing del usuario autenticado.
- `MySQLServiceRepository`: nuevo método (o reutilización de `updateListing`) que quita la URL del array `gallery` y persiste `json_encode($gallery)`.
- Si la URL borrada era la `imageUrl` (portada activa): reasignar automáticamente la portada a la primera foto restante del `gallery` (o dejar `imageUrl` vacío si no queda ninguna).
- Borrado físico del archivo: solo si la URL apunta a la carpeta de uploads gestionada por la app (mismo prefijo que usa `$uploader->store()`); las URLs externas/seed no se tocan.
- Respuesta JSON: `{success, imageUrl, gallery}` — mismo contrato que ya consume el JS en línea 156-166.

**Frontend**
- Botón "×" superpuesto en cada thumbnail existente: tanto en `.ficha-cover-option` (picker de portada) como en `#ficha-gallery-preview` (galería de la ficha).
- `confirm()` nativo antes de disparar el borrado (acción irreversible, sin necesidad de modal propio).
- Fetch a la nueva acción; al recibir la respuesta, repinta portada + galería con los datos devueltos (reutiliza el patrón ya existente de repintado de `#ficha-gallery-preview`).

## 2. Reordenado por drag & drop

- Drag & drop nativo HTML5 (`draggable`, `dragstart`/`dragover`/`drop`) sobre los thumbnails de `#ficha-gallery-preview` y/o `.ficha-cover-picker`. Sin librerías — el número de fotos es pequeño.
- Al soltar, se reordena el DOM inmediatamente (feedback visual instantáneo).
- El nuevo orden se envía como array de URLs al guardar el modal (input oculto o payload del fetch de guardado existente).
- Backend: persiste `gallery` en el orden recibido — mismo campo, sin cambio de esquema.

## 3. Validación cliente + estado de carga

- Antes de enviar: valida `file.size` (máx. 8MB) y `file.type` contra la lista `accept` ya declarada en los `<input type="file">`. Si falla, muestra mensaje de error inline junto al dropzone correspondiente (reutiliza `.ficha-modal-error`, ya presente en el modal) y no envía.
- Durante el envío: estado "Subiendo…" con spinner simple + botón submit deshabilitado. No se implementa barra de progreso real (requeriría cambiar `fetch` por `XMLHttpRequest`); para fotos de tamaño normal en un panel interno es sobre-ingeniería. Si en el futuro se suben archivos grandes y se nota lento, escalar a XHR con evento `progress`.

## Archivos afectados

- `src/Infrastructure/Views/backoffice/edit_listing.php` — botones de borrado en thumbnails, atributos `draggable`.
- `public/assets/js/backoffice-edit.js` (o `backoffice-map.js` si aplica) — lógica de borrado, drag & drop, validación y estado de carga.
- `src/Infrastructure/Controllers/BackofficeController.php` — nueva acción `delete_photo`, envío de orden de galería.
- `src/Infrastructure/Persistence/MySQLServiceRepository.php` — soporte para persistir `gallery` sin el elemento borrado / en nuevo orden.

## Criterios de éxito

- Se puede borrar cualquier foto de la galería o la portada desde el modal, sin tocar la BD a mano.
- Al borrar la portada activa, la ficha pública sigue mostrando una imagen válida (reasignación automática) sin romper el layout.
- El orden de arrastre en el modal se refleja en el carrusel de la ficha pública tras guardar.
- Un archivo demasiado grande o de tipo no permitido se rechaza antes de la subida, con mensaje claro en español/francés según corresponda al idioma de la UI del backoffice.
- Ninguna foto externa/seed (fuera de `/uploads`) se borra del disco por error.
