# TASK-044 — Planificateur de séjour 2026 (`/planificateur-2026/`) — Diseño

Fecha: 2026-10-01 · Estado: aprobado en conversación (secciones 1–3), autorrevisado, pendiente de revisión del usuario
Mockup aprobado: `docs/mockups/planificateur-2026.html` (versión 10 del artifact)

## Objetivo

Llevar al WordPress de producción el planificador de la app local (`/fr/vacation-planner`) con un diseño
nuevo: un **asistente conversacional** que compone un séjour día a día con prestatarios reales del canal y,
si el usuario quiere, **pide disponibilidad a esos prestatarios** tras confirmar su e-mail. Se sirve desde el
plugin `canal-home`, **sin modificar nada existente**, como página privada hasta orden explícita.

Éxito =
1. Un usuario con sesión abre `/planificateur-2026/` (sin sesión → 404) y ve el mockup aprobado con el navbar
   real 2026, sin scroll de página a 1440×900, 1366×768, 390×844 y 320×640.
2. Escribe o elige una idea y recibe en el chat un plan por días con km del canal y enlaces a fichas 2026
   reales; puede ajustarlo con mensajes (« plus calme », « un jour de plus »).
3. Pulsa « Demander les disponibilités »; si faltan fechas o personas la IA las pregunta; da su e-mail en el
   composer; recibe un e-mail de confirmación; al confirmar, cada prestatario con e-mail recibe su demanda y
   las fichas sin e-mail llegan al buzón de France Édition.
4. En modo desarrollo todos los correos llegan a `onavarro@francedit.com` con el destinatario real en el asunto.

## Decisiones del usuario (2026-10-01)

- Flujo completo como en local (envío a prestatarios), con mejoras de la investigación (calidad del plan y
  valor de negocio). Confirmación por e-mail antes de escribir a nadie.
- UI: la del mockup v10 (orbe IA, composer con placeholder que se escribe solo, ideas por tema, chat). Página
  **sin scroll**; nada se mueve al actualizar las ideas; el orbe nunca reinicia su animación; título en una
  línea « Quel séjour imaginez-vous ? »; sin disclaimer bajo el composer.
- Prestatarios sin e-mail (122 de 254 fichas publicadas): su demanda va a `mbauwens@francedit.com`.
- Desarrollo: todos los correos → `onavarro@francedit.com`.
- « Comment venir » (trayecto desde casa, enlaces Omio afiliados) → **fase 2**, fuera de esta spec.
- **Sin gestión desde wp-admin** (decisión del usuario 01/10, excepción a su regla general): textos, temas e
  ideas viven en PHP (`planner-core.php`, constantes con los valores del mockup).

## 1. Arquitectura

Mismo patrón que carte y ficha (`CANAL_CARTE_PATH`, `CANAL_FICHE_PATH`).

| Archivo (nuevo) | Responsabilidad |
|---|---|
| `canal-home/template-planner.php` | Plantilla de la página: `get_header()` (head + navbar real de `includes/header.php`) y `get_footer()` como la carte, pero en modo **pantalla única**: clase `cdm-planner` en `<body>` y CSS que oculta el pie 2026, el `footer.footer` del tema y el bloque `hit-billboard` **solo en esta página**, y fija la altura a `100dvh − navbar` |
| `canal-home/assets/planner.css` | CSS del mockup, tokens 2026; generado por `build/` si aplica, nunca a mano en el generado |
| `canal-home/assets/planner.js` | Composer, ideas, chat, llamadas REST; vanilla, `defer` |
| `canal-home/includes/planner-core.php` | **Funciones puras** (testeables sin WP): prompt, validación del plan, reparto de destinatarios, plantillas de correo, token, desvío de pruebas |
| `canal-home/includes/planner.php` | Ruta privada, endpoints REST, tabla, `wp_mail`, WP-Cron J+3 |
| (en `planner-core.php`) | Plantillas de los 4 correos (`canal_planner_mail_*`) |
| `tests/test-planner-core.php`, `tests/smoke-planner.php` | Tests (§6) |

Se reutiliza: `ai-core.php` (petición a Claude, catálogo, respaldo sin `json_schema` ante 503 « Grammar
compilation »), `ai.php::canal_home_ai_config()` (clave, modelo, tope diario), `limits.php`
(`canal_home_rate_hit`, `canal_home_daily_hit`), `canal_fiche_url()` para los enlaces.

Ruta: constante `CANAL_PLANNER_PATH = '/planificateur-2026/'`; página WP nueva privada con la plantilla
(como 18500/18502). Sin sesión → 404 (mismo control que la ficha).

## 2. Flujo y contratos

```
composer ──POST canal-home/v1/plan {messages[], plan?}──▶ Claude (catálogo + km)
   ◀── {reply, plan:{title, mode, days[{label, km, place, text, slug}], when?, people?, missing[]}}
"Demander les disponibilités" ──(si plan.missing ≠ [] la IA pregunta fechas / personas en el chat)
e-mail en el composer ──POST canal-home/v1/plan/request {plan, email, website(honeypot)}
   ──▶ fila « pending » + correo 1 (confirmación)
clic ──GET /planificateur-2026/?confirmer=<token> ──▶ la página muestra el resumen + botón « Confirmer l'envoi »
 botón ──POST canal-home/v1/plan/confirm {token} ──▶ correos 2 y 3, correo 4a, J+3 programado
   ──▶ en la misma pantalla: « Vos demandes sont parties » / « Ce lien a expiré »
```

- **La confirmación exige un clic en la página** (POST), no basta con abrir el enlace: los antivirus de correo
  (p. ej. Outlook Safe Links) abren los enlaces solos y confirmarían demandas que nadie ha validado.
- **Conversación en el navegador.** Cada llamada envía como máximo los 8 últimos mensajes + el plan actual.
  Sin sesión en servidor.
- **Catálogo**: el de la home (`canal_home_ai_catalog()`: slug, nombre, categorías, commune, extracto) **ampliado**
  en un transient propio `canal_planner_catalog` (12 h) con `km` y `has_email`. El `km` (PK) se calcula
  proyectando `geolocation_lat/long` sobre la polilínea de las 8 étapes con km conocido (Toulouse 0 … Thau 240)
  e interpolando. Precisión estimada ±5 km: suficiente para el control de km/día (ponytail: si no basta, trazar
  la polilínea con más puntos del canal). Fichas lejos del canal (> 15 km) se marcan `km = null` y no cuentan
  para el control de distancia.
- El bloque del catálogo va con `cache_control: ephemeral`, como ya hace `canal_home_build_request()`.
- **Validación en PHP** (`planner-core.php`), que la IA no puede saltarse:
  - todo `slug` debe existir en el catálogo; los desconocidos se descartan;
  - distancia entre días consecutivos ≤ máximo del modo (bateau 30 km, vélo 50 km, à pied 20 km,
    voiture sin límite);
  - máximo 6 prestatarios « demandables » por plan.
  Plan inválido → un reintento silencioso con el error; si vuelve a fallar → mensaje §5.
- **Modelo**: el de `canal_home_ai_config()`; respuesta estructurada con `json_schema` y respaldo existente.

## 3. Datos

Tabla nueva `{$wpdb->prefix}canal_plan_requests`, creada con `dbDelta` cuando la opción
`canal_planner_db_version` no coincide con la versión del código (el plugin ya está activo: un hook de
activación no se ejecutaría al desplegar). Nada existente se toca:

| Columna | Tipo | Nota |
|---|---|---|
| `id` | BIGINT PK | |
| `token_hash` | CHAR(64) UNIQUE | `hash('sha256', token)`; el token en claro solo va en el e-mail |
| `email` | VARCHAR(190) | |
| `plan` | LONGTEXT | JSON validado |
| `when_text`, `people_text` | VARCHAR(190) | extraídos por la IA / preguntados |
| `status` | VARCHAR(20) | `pending` → `sent` / `expired` |
| `recipients` | LONGTEXT | JSON: slug, destino (`provider` / `fe_inbox`), resultado de `wp_mail` |
| `created_at`, `confirmed_at` | DATETIME | |

- Purga diaria (WP-Cron) de filas con más de **12 meses**; `pending` sin confirmar a las 48 h → `expired`.
- **Contador por ficha**: consulta sobre `recipients` (base del argumento de renovación « votre fiche a généré
  N demandes »); además evento **GA4** (`gtag`, el que usa el sitio; no hay Matomo en el WP) `planner_request` con
  `listing_slug` al confirmar, y `planner_plan` al generar un plan.

## 4. Correos (`wp_mail`, HTML con la marca 2026)

| # | Destino | Momento | Contenido |
|---|---|---|---|
| 1 | Usuario | Al dar el e-mail | « Confirmez votre demande » + botón (48 h), resumen del plan y lista de destinatarios |
| 2 | Cada prestatario con `_job_email` | Tras confirmar | Asunto « Demande de disponibilité · {dates} · {personnes} »; su día y prestación, mensaje del usuario; **Reply-To = usuario** |
| 3 | `mbauwens@francedit.com` | Tras confirmar, si hay fichas sin e-mail | Igual que 2 + nombre, teléfono y enlace de cada ficha sin e-mail, para gestión manual |
| 4 | Usuario | Tras confirmar / a J+3 | Plan completo con enlaces a fichas; J+3 « Avez-vous reçu des réponses ? » (`wp_schedule_single_event`) |

- **Modo desarrollo** (implementado al revés, más seguro): mientras `CANAL_PLANNER_LIVE` no esté definida a `true` en `canal-ai-config.php` (fuera del repo),
  **todo** destinatario se sustituye por ella y el asunto lleva `[TEST → <destinatario real>]`.
  Destino de pruebas: `onavarro@francedit.com` (`CANAL_PLANNER_DEV_TO`). Pasar a producción = definir `CANAL_PLANNER_LIVE` a `true` en `canal-ai-config.php`.
- Buzón FE en constante `CANAL_PLANNER_FE_INBOX = 'mbauwens@francedit.com'`.
- Seguridad: todo texto del usuario escapado en HTML; asunto y `Reply-To` sin `\r`/`\n` (CRLF injection);
  `Reply-To` solo si el e-mail pasa `is_email` + `FILTER_VALIDATE_EMAIL`.

## 5. Antispam, límites y errores

Límites (`limits.php`):
- IA: 10 mensajes / 10 min por IP (como la home) + **tope diario propio** `CANAL_PLANNER_DAILY_CAP` (300,
  contador `canal_planner_ai_daily_YYYYMMDD`) para que la conversación no agote el de la búsqueda IA de la home.
- Cada mensaje del usuario ≤ 500 caracteres y pasa por `canal_home_sanitize_prompt()`.
- Demandas: 3 / hora por IP, 2 / día por e-mail, 100 / día en total, máx. 6 prestatarios por demanda.
- Honeypot `website` en `plan/request` (respuesta « ok » sin enviar, como `plan.php`).
- Token aleatorio (`random_bytes(32)`), un solo uso, caduca a las 48 h, guardado solo como hash.

Errores, siempre en el chat y en francés:

| Caso | Respuesta |
|---|---|
| Claude no disponible (tras el respaldo) | « L'assistant est momentanément indisponible. » + enlace « Explorer la carte » |
| Límite IP / tope diario | « Trop de demandes en peu de temps, réessayez dans quelques minutes. », composer bloqueado |
| Plan inválido dos veces | « Je n'ai pas trouvé de séjour cohérent, précisez votre envie. » |
| E-mail inválido | La IA lo vuelve a pedir |
| Enlace caducado o usado | Página 2026 « Ce lien a expiré » + « Refaire ma demande » |
| `wp_mail` falla con un destinatario | Se registra en `recipients`; los demás se envían igual |

## 5 bis. Restricciones técnicas

- Producción en **PHP-FPM 7.4**: nada de `match`, `str_contains`, enums ni tipos unión (lo comprueba el lint
  7.4 de `remote.sh test`).
- Latencia: cada mensaje es una llamada a Claude con el catálogo (≈ 7–10 s con el modelo actual). El chat
  muestra el indicador « écrit… » y el orbe « pensando »; el plan llega de una vez (sin streaming en v1).
- El aviso de cookies del sitio (icono abajo a la derecha) no debe tapar el botón de envío en móvil: verificar.

## 6. Tests y verificación

- `tests/test-planner-core.php` (puro, en `remote.sh test` con PHP 7.4): validación de slugs y km/día por
  modo, máx. 6 prestatarios, limpieza del prompt, reparto proveedor / buzón FE, plantillas (escapado, sin
  CRLF en cabeceras, Reply-To), token (hash, caducidad, un solo uso), desvío sin `CANAL_PLANNER_LIVE`.
- `tests/smoke-planner.php`: 404 sin sesión, 200 con sesión, endpoints responden con el JSON esperado.
- Navegador (capturas tras scroll-reveal): 1440×900, 1366×768, 390×844, 320×640 sin scroll de página y sin
  desplazamientos al cambiar de tema (medición de `getBoundingClientRect`, como en el mockup); flujo
  completo con correos reales recibidos en `onavarro@francedit.com`; consola sin errores.

## 7. Publicación

- **Ahora (privada)**: página nueva, tabla nueva, constante de desarrollo. Nada existente cambia.
- **Pública — SOLO con orden explícita**, junto con TASK-028 / 029b / 030b: quitar `-2026`
  (`CANAL_PLANNER_PATH`), definir `CANAL_PLANNER_LIVE = true`, apuntar « Planifier mon voyage » del navbar
  (hoy `home#plan`) al planificador, decidir el destino de `/organiser-votre-sejour/`, confirmar que el bloque publicitario `hit-billboard` puede
  ocultarse en esta página, revisar
  `pm.max_children` (cada mensaje ocupa un worker PHP durante la llamada a Claude).
- **Rollback**: retirar los archivos `planner*` del plugin, papelera de la página, `DROP TABLE
  {prefix}canal_plan_requests`, `wp option delete canal_planner_db_version`, borrar transients `canal_planner_*`,
  desprogramar los eventos cron (J+3 y purga).

## Fuera de alcance

- Fase 2: « Comment venir » (origen del viajero → puerta de entrada → enlaces Omio afiliados).
- Cuentas de usuario, guardado de planes, mapa interactivo, pagos o reservas.
- Multilingüe (el sitio WP es francés).

## Cambios aprobados por el usuario tras la implementación (2026-10-02)

Prevalecen sobre las secciones anteriores. Mockup: `docs/mockups/planificateur-2026-plan.html`.

- **Vista plan:** mientras la IA solo pregunta, chat centrado; desde la primera propuesta, chat a la izquierda (400 px) y
  plan a la derecha (itinerario vertical con km, tramo y tiempo estimado, foto de portada de cada ficha a 768 px,
  categoría). En móvil el plan ocupa la pantalla y el chat es una hoja inferior. Los ajustes actualizan el plan en su sitio.
- **Orbe:** mismo estilo y animación que el del inicio en todos los tamaños.
- **Demanda en un modal** (no en el chat): resumen, Google Maps con el recorrido (marcadores numerados y polilínea,
  cargado solo al abrir, misma URL que registra el tema), lista de prestatarios, campos fechas / personas (rellenados,
  editables, obligatorios) / e-mail / mensaje opcional (≤ 500, texto plano, llega a prestatarios y buzón FE).
  `/plan/request` acepta `when`, `people`, `message`; columna `message` (BD v2).
- **Idioma:** la IA responde en el idioma del último mensaje del visitante; la interfaz fija y los correos siguen en francés.
- **Preguntas antes de proponer:** si faltan fechas/duración, personas o modo/envies y no hay plan, la IA pregunta todo en
  un solo mensaje (una sola vez) sin proponer. Una tarjeta de preguntas con opciones (estilo claude.ai) se probó en el
  mockup y se descartó.
- **Revisión final:** una sola demanda viva por e-mail (la nueva anula el enlace anterior); el correo de confirmación solo
  lleva nombres del catálogo (no el título ni los textos del plan, que vienen del navegador); GA `planner_request` con
  `listing_slug` por ficha.
- « Planifier mon voyage » de la cabecera 2026 ya apunta al planificador (solo aparece en páginas 2026 privadas).
