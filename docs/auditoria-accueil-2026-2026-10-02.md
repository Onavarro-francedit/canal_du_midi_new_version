# Auditoría SEO / AEO / GEO — `/accueil-2026/` (2026-10-02)

Fuentes: HTML real (página abierta ~5 min y vuelta a privada), OpenSEO (crawl + Lighthouse
DataForSEO), Search Console y GA4 (vía OpenSEO), Perplexity `sonar` con búsqueda web (DataForSEO,
3 preguntas, 0,02 $). Escala de 100 puntos de la skill `seo-geo`.

## Puntuación: 84/100

| Dimensión | Puntos | Comentario |
|---|---|---|
| Técnico | 16/20 | LCP móvil 5,7 s (−3); bots de entrenamiento bloqueados a propósito (−1) |
| On-page | 15/15 | title 57 car., meta 157, OG 1200×630, Twitter, 1 H1, 157 enlaces internos |
| Schema | 19/20 | Organization, WebSite, WebPage+Speakable, TouristDestination, ItemList (8), FAQPage (6). JSON válido; falta pasar el Rich Results Test (−1) |
| GEO | 20/25 | Cifras citables (240 km, 63 écluses, 1681, UNESCO), llms.txt + llms-full + cabecera Link. `Organization.sameAs` solo FB + IG, sin Wikidata (−4) |
| E-E-A-T | 7/10 | Sin autor/editor visible con credenciales |
| AEO | 7/10 | H2 en pregunta + FAQ; sin HowTo (no se recomienda: Google retiró el rich result) |

## Hallazgos por prioridad

1. **ALTA — Intención «carte / plan / tracé».** En 90 días la home actual (`/`) recibe ~19.000
   impresiones por búsquedas de mapa: «canal du midi carte» 6.843 (pos. 6,6), «tracé du canal du
   midi» 1.817, «canal du midi carte détaillée» 1.659, «parcours … carte détaillée» 1.244, «carte
   canal du midi» 1.237, «parcours … carte gratuite» 850. La home 2026 no las cubre en title ni H1
   («bateaux, vélos, hébergements») y no usa nunca «tracé», «parcours» ni «détaillée». Al sustituir
   `/` se pueden perder. Además «canal du midi» = 68.945 impr., pos. 9,6, CTR 0,5 %.
   Perplexity confirma la autoridad del sitio: solo lo cita en la pregunta sobre la carte.
2. **MEDIA — LCP móvil 5,7 s** (objetivo < 2,5 s). Rendimiento 72 móvil / 91 escritorio.
3. **MEDIA — Entidad de la marca.** «L'Officiel du Canal du Midi» no tiene Wikidata ni ficha de
   Google Business Profile en `sameAs`. Perplexity no cita el sitio en «que faire» ni en «louer un
   bateau» (cita tourismecanaldumidi.fr, canal-du-midi.com, lescanalous.com, leboat.com…).
4. **BAJA — Salto de encabezados: viene del tema my-listing**, no de la plantilla: modal de login
   (`<h5>Se connecter</h5>`) y carrito (`<h2>Panier</h2>`) al final del HTML. También 2 `<img>`
   sin `alt` del tema (`pin.png` y una plantilla con `src` vacío).
5. **Comprobar al publicar:** el canonical y `og:url` apuntan a `/accueil-2026/`; deben pasar a `/`.

## Lo que ya está bien
robots.txt con Content-Signal (`ai-train=no`) y bots de búsqueda IA permitidos; GPTBot/ClaudeBot
reciben 403 a propósito. HSTS y nosniff. Sitemap `wp-sitemap.xml` 200. FAQ visible en `<details>`
y en schema. Sin enlaces rotos.
