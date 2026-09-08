# Diseño — Aspecto de dashboard para /backoffice (vista admin)

## Contexto
`/fr/backoffice` sin `id` renderiza `admin_dashboard.php` para un usuario admin.
Hoy es un `.backoffice-userbar` suelto + `<h1>` + tres `<section class="backoffice-fieldset">`
planas (buscador+tabla de fichas, crear cuenta owner, tabla de cuentas owner). Cero
aspecto de panel de administración.

## Alcance
Solo el cascarón visual de `admin_dashboard.php` y las clases CSS que usa en
`backoffice.css`. No se toca `edit_listing.php` (vista owner, réplica de la ficha
pública), ni `BackofficeController.php` (sin cambios de datos ni de lógica).

## Cambios

1. **Topbar fija** (`.admin-topbar`): nombre del sitio a la izquierda, email del
   admin + botón "Déconnexion" a la derecha. Reemplaza `.backoffice-userbar`.
2. **Stat cards** (`.admin-stats`): "Fiches" = `count($allListings)`, "Comptes
   propriétaires" = `count($owners)`. Datos ya disponibles en el controller, sin
   queries nuevas.
3. **Secciones como tarjetas** (`.admin-card`, sustituye `.backoffice-fieldset`):
   borde + `--radius-lg` + `--shadow` (tokens ya definidos en `styles.css`),
   cabecera con icono, mismo lenguaje visual que `.section-card` de la ficha.
4. **Tablas**: hover de fila, columna de acción como botón/pill en vez de link
   pelado.

Explícitamente fuera de alcance (YAGNI): sidebar de navegación (una sola página
real hoy), gráficas, métricas sin tracking real (vistas, reservas).

## Archivos afectados
- `src/Infrastructure/Views/backoffice/admin_dashboard.php`
- `public/assets/css/backoffice.css`
