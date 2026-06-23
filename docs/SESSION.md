# SESSION.md — Canal du Midi

Estado exacto de la última sesión. Es lo primero que se lee al abrir cualquier
sesión.

---

## Incremento 1 del rediseño de home — COMPLETADO ✅ (verificado en navegador)

**Fecha:** 2026-06-23
**Agente activo al cerrar:** product / verificación
**Handoff pendiente:** ninguno.

**Dónde quedamos:** TASK-009 (tokens de arte) + TASK-010 (hero WOW orquestado)
pasaron todo el pipeline (architect → coder → security ✅) y **se verificaron en
navegador** (PRD-002, la lección de TASK-008). El hero rediseñado funciona y se
ve bien; movido a 🟢 Completadas.

**Verificación en navegador (Playwright + Chrome):**
- `html.hero-ready` aplicado; entrada escalonada visible; Ken Burns lento activo.
- Parallax real solo de `.hero-card-img` (translate −5px→22.8px al hacer scroll).
- h1 en Playfair Display con `<em>lent.</em>` en itálica; 4 stats reales
  (`240 km · 63 écluses · Toulouse → Méditerranée · UNESCO`).
- Ligne d'eau dibujada (`stroke-dashoffset≈0`). 0 overflow, 0 errores de consola.
- `prefers-reduced-motion`: h1/stats/ligne visibles y estáticos, sin animación.
- Capturas en scratchpad: `H1-hero-loaded.png`, `H2-hero-reduced.png`.

**Archivos del Incremento 1:** `styles.css` (tokens + orquestación hero +
reduced-motion), `header.php` (Playfair recto), `home.php` (h1 `<em>`, stats
reales, SVG ligne d'eau), `home-hero.js` (NUEVO, vanilla), `footer.php` (carga
condicional), `docs/ARCHITECTURE.md` (decisión de arte).

**Pulido posterior (2026-06-23, verificado en navegador):** dos fallos que vio el
usuario, corregidos en `styles.css`/`home.php`:
- Ken Burns desbordaba la overlay → `overflow:hidden` en `.hero-card` (la imagen
  escalada se recorta; sin franja clara en bordes).
- Stats se partían y eran poco atractivas → tira translúcida con 4 métricas en una
  línea + separadores + versalitas; se reemplazó "Toulouse → Méditerranée" por
  `1681` (año de creación) para homogeneizar. Móvil 2×2.
- Acento terracota aplicado al eyebrow del hero (`#C16B43`).
Verificado: card `overflow:hidden`, stats en 1 fila (desktop) / 2×2 (móvil),
eyebrow rgb(193,107,67), 0 overflow, 0 errores. Captura `F1-hero-fixed.png`.
Watch menor: el eyebrow terracota queda algo sutil sobre el follaje claro (lleva
text-shadow; legible pero discreto).

**Pulido 2 (2026-06-23, verificado en navegador):**
- Eyebrow → terracota luminosa `#F2A86E` + peso 800 + sombra fuerte (legible
  sobre el follaje).
- Buscador movido al flujo DENTRO del hero (era `position:absolute; bottom:-82px`),
  ahora tarjeta blanca flotante a ~30px bajo las stats (sombra fuerte sobre la
  imagen). Se le quitó `data-reveal` (lo anima solo `hero-ready`). `.hero-card-content`
  ensanchado a 980px para que el grid del buscador no se parta.
- Verificado: search dentro de la card (gap 30px), única instancia, 0 overflow,
  móvil ok. Captura `G1-search-moved.png`.

**Próxima acción exacta (Incremento 2):**
```
/agent architect Inc.2 — TASK-011 (ligne d'eau como divisor entre secciones, reutiliza el SVG del hero) + TASK-013 (micro-interacciones hover de destination-card/tour-card/botones)
```
