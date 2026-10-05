# Calcul de distance 2026 — plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans. Ejecución inline en la sesión principal.

**Goal:** página `/calcul-de-distance-canal-du-midi-2026/` que reproduce la maqueta aprobada en el plugin `canal-home`.
**Architecture:** núcleo puro con los datos y el cálculo (`calcul-core.php`, con test) → ruta, SEO y fotos
(`calcul-route.php`) → plantilla renderizada en el servidor → JS para el cálculo en vivo y el mapa diferido.
**Spec:** `docs/superpowers/specs/2026-10-05-calcul-distance-2026-design.md` · **Maqueta:** `docs/mockups/calcul-distance-2026.html`

## Global Constraints
- PHP 7.4; solo añadir; sin Pimcore; privada (`read_private_pages` o la opción `canal_calcul_public`).
- Constantes del modelo: 7 km/h, 10 min por sas, 6 h de barco al día; bici 15 km/h, 60 km en el día, 55 km/día;
  a pie 4 km/h, 20 km/día.

## Review Focus
1. Tramo con salida en una esclusa: esa esclusa no cuenta; con llegada en una esclusa, sí.
2. `?de=` con otra grafía (« trebes », « ÉCLUSE D'HOMPS »): se resuelve sin acentos ni mayúsculas.
3. Sin clave de Maps: la página no rompe.
4. Móvil: Google Maps no se descarga hasta pulsar el botón.
5. JSON-LD sin `</script>` interno (`canal_home_seo_jsonld`).

### Task 1: núcleo + test (`calcul-core.php`, `tests/test-calcul.php`, `remote.sh`)
- [ ] test que falla → implementar → `php tests/test-calcul.php` TODO OK → commit.

### Task 2: ruta, plantilla, CSS, JS, mapa, integraciones
- [ ] `calcul-route.php`, `template-calcul.php`, `assets/calcul.css`, `assets/calcul.js`, `assets/calcul-map.js`,
  `assets/calcul/canal-du-midi-trace.json`; integraciones en `canal-home.php`, `header.php`, `head-fix.php` y
  `fiche-route.php`.
- [ ] `remote.sh test` → `deploy` → `curl` (404 sin sesión) → commit.

### Task 3: verificación en el navegador y docs
- [ ] 1440 y 390 px (iframe), mapa, ventanita, lista, ⇄, URL; `docs/TASKS.md`, `docs/SESSION.md`, `CLAUDE.md`; commit.
