# Diseño — Migración de la IA de OpenAI a Claude (Sonnet 4.6)

- **Fecha:** 2026-07-06
- **Estado:** aprobado (diseño), pendiente de plan de implementación
- **Autor:** pipeline de brainstorming (Claude)

## Objetivo

Migrar la integración de IA del proyecto de **OpenAI** a **Claude** (`claude-sonnet-4-6`),
para obtener mejor manejo de contextos largos y calidad de recomendación. Alcance:
los **dos** servicios que hoy usan OpenAI —la búsqueda semántica de la página `search`
y el planificateur de viajes— más el prompt caching del catálogo para reducir coste y
latencia en peticiones repetidas.

## Decisiones (confirmadas con el usuario)

| Decisión | Elección |
|---|---|
| Alcance | Búsqueda IA (`OpenAIService`) **+** `VacationPlannerService` |
| Integración | SDK oficial `anthropic-ai/sdk` vía Composer (ya presente en el proyecto) |
| Modelo | `claude-sonnet-4-6` (contexto 1M, buen equilibrio calidad/coste/latencia) |
| Ambición | Reemplazo directo **+ prompt caching** del catálogo |
| `sanitizeUserPrompt()` | Extraer a un sitio común (trait `SanitizesPrompts`) |

## Contexto actual (verificado en código)

- `src/Domain/Services/AIServiceInterface.php` — puerto: `analyzeRequest(string $prompt, array $availableServices): array`.
- `src/Infrastructure/Services/OpenAIService.php` — implementa el puerto; llama a
  `POST https://api.openai.com/v1/chat/completions` vía curl crudo. Mete **todo el
  catálogo** en el `system` prompt en cada petición. Fallback → `SmartAIService`.
- `src/Infrastructure/Services/VacationPlannerService.php` — clase independiente (no
  implementa el puerto); mismo patrón curl+OpenAI. Fallback → método `fallback()` interno.
- `src/Infrastructure/Controllers/PageController.php:577` — `new OpenAIService()` (único
  punto de acoplamiento de la búsqueda).
- `src/config/config.php:38-40` — define `OPENAI_API_KEY`, `OPENAI_MODEL`.
- `autoload.php:2` — ya hace `require_once __DIR__ . '/vendor/autoload.php'` (Composer activo;
  PhpMailer ya instalado). PHP 8.2.4, `composer` disponible en PATH.
- `sanitizeUserPrompt()` está **duplicado** literalmente en ambos servicios.
- Hardening anti-inyección existente (LESSONS SEC-002/SEC-010): sanitización, delimitadores
  `<<<DEMANDE_UTILISATEUR>>>...<<<FIN_DEMANDE_UTILISATEUR>>>`, cláusula anti-override en el system.

## Arquitectura objetivo

```
                 PageController (search)                 vacation-planner
                        │                                       │
                        ▼                                       ▼
              ClaudeAIService  ─implements─►  AIServiceInterface │
                        │                                       │
                        └──────────► Anthropic\Client ◄─────────┘
                                    (SDK oficial, messages->create)
                              usa trait SanitizesPrompts (compartido)
   Fallback: SmartAIService (búsqueda) / ::fallback() interno (planner)
```

### Unidades

1. **`SanitizesPrompts` (trait, nuevo)** — `src/Infrastructure/Services/SanitizesPrompts.php`,
   namespace `App\Infrastructure\Services`. Único método `sanitizeUserPrompt(string $raw, int $maxLen): string`
   (idéntico al actual: trim, colapsar saltos, eliminar `DEMANDE_UTILISATEUR`, `mb_substr`).
   Lo usan ambos servicios. Elimina la duplicación.

2. **`ClaudeAIService` (nuevo)** — `src/Infrastructure/Services/ClaudeAIService.php`,
   `implements AIServiceInterface`, `use SanitizesPrompts`. Reemplaza a `OpenAIService`.
   Conserva la lógica de normalización de servicios (`normalizeService`, `buildResultsFromIds`)
   y el formato de retorno (`id, title, type, price, text, results, count`). Fallback →
   `new SmartAIService()`.

3. **`VacationPlannerService` (reescritura parcial)** — se conserva `buildCatalog()`,
   `hydrate()`, `fallback()`; se cambia el bloque de llamada API por el SDK de Claude;
   `sanitizeUserPrompt()` pasa a venir del trait.

4. **`OpenAIService`** — se elimina (recuperable desde git).

## Detalles de la API de Claude (diferencias que obligan cambios)

Referencia: skill `claude-api` (PHP). Puntos concretos:

1. **Construcción del cliente:** `new Anthropic\Client(apiKey: ANTHROPIC_API_KEY)`.
2. **`system` como bloque cacheado (prompt caching):** el catálogo (bloque grande y estable)
   va en `system` como array de bloques de texto, con `cacheControl => ['type' => 'ephemeral']`
   en el último bloque. En peticiones repetidas dentro de la ventana de caché (5 min) el
   catálogo se lee a ~0.1× del precio y baja la latencia. Mínimo cacheable en Sonnet 4.6 =
   2048 tokens (el catálogo lo supera). El `system` debe mantenerse **byte-estable** (nada de
   timestamps ni IDs por petición dentro del prefijo cacheado); el input variable del usuario
   va en `messages`, después del prefijo.
3. **Fin del mensaje `assistant` con el esquema (prefill):** hoy la búsqueda añade un tercer
   mensaje `role: assistant` con la forma del JSON esperado. En Sonnet 4.6 un prefill en el
   último turno **devuelve 400**. Se sustituye por **structured outputs**:
   `outputConfig => ['format' => ['type' => 'json_schema', 'schema' => [...]]]`, que además
   garantiza JSON válido y reemplaza también el `response_format: json_object` del planner.
   - Restricciones del schema: objetos con `additionalProperties: false` y `required`; sin
     `minLength`/`maxLength`/`minimum`/`maximum` (no los usamos).
4. **Parseo de respuesta:** `$message->content` es un array de bloques polimórficos; se
   recorre buscando el bloque `type === 'text'` y se hace `json_decode($block->text, true)`
   (con structured outputs ya es JSON válido). Nunca acceder a `content[0]->text` a ciegas.
5. **Sin `thinking`** (búsqueda/plan rápidos; en Sonnet 4.6 el thinking está apagado si no se
   envía el parámetro). `maxTokens`: ~1500 (búsqueda) / ~2000 (plan). No requiere streaming
   (< 16K). `temperature: 0.7` sigue aceptándose en Sonnet 4.6 (se conserva el comportamiento).

## Config y `.env`

En `src/config/config.php`:
```php
define('ANTHROPIC_API_KEY', $_ENV['ANTHROPIC_API_KEY'] ?? '');
define('ANTHROPIC_MODEL',   $_ENV['ANTHROPIC_MODEL']   ?? 'claude-sonnet-4-6');
```
- Las constantes `OPENAI_API_KEY` / `OPENAI_MODEL` quedan huérfanas (grep confirma que solo
  las usaban estos 2 servicios) → se eliminan.
- `.env`: el usuario añade `ANTHROPIC_API_KEY=...` (secreto, no se commitea). `ANTHROPIC_MODEL`
  es opcional (default en config).
- `composer require anthropic-ai/sdk` (actualiza `composer.json` y `vendor/`).

## Seguridad (no regresar)

- Hardening anti-inyección **intacto**: trait `sanitizeUserPrompt` + delimitadores +
  cláusula anti-override en el system (SEC-002/SEC-010).
- API key solo en `.env`, nunca en código ni en logs.
- El catálogo cacheado no incluye datos sensibles ni secretos.

## Manejo de errores y fallback

`try/catch` sobre excepciones tipadas del SDK (`Anthropic\Core\Exceptions\RateLimitException`,
`APIStatusException`, `APIConnectionException`) y sobre respuesta malformada → se retorna el
fallback existente (`SmartAIService::analyzeRequest` en búsqueda; `::fallback()` en el plan).
Si `ANTHROPIC_API_KEY` está vacía, ir directo al fallback (igual que hoy con OpenAI). Se
registra el error con `error_log` (sin volcar datos sensibles).

## Criterios de éxito

1. La búsqueda IA de `/search` devuelve recomendaciones **reales** del catálogo vía Claude,
   con explicación en francés, respetando la intención (náutico/hotel/resto/bici/camping).
2. El planificateur de `/vacation-planner` genera un itinerario día-por-día con `service_id`
   reales, hidratado correctamente.
3. Fallback funciona sin API key o ante error de API (no rompe la página).
4. Prompt caching activo: `usage.cacheReadInputTokens > 0` en la 2ª búsqueda idéntica dentro
   de 5 min.
5. Sin regresión de seguridad: intentos de override en el prompt no cambian el rol del modelo.
6. **Verificación en navegador (Playwright)** — regla del proyecto PRD-002/003: búsqueda IA
   real + plan real renderizados, 0 errores de consola. Más un smoke test PHP directo.

## Archivos afectados

| Archivo | Cambio |
|---|---|
| `composer.json` / `vendor/` | `composer require anthropic-ai/sdk` |
| `src/config/config.php` | +`ANTHROPIC_*`, −`OPENAI_*` |
| `.env` | +`ANTHROPIC_API_KEY` (lo pone el usuario) |
| `src/Infrastructure/Services/SanitizesPrompts.php` | **nuevo** trait |
| `src/Infrastructure/Services/ClaudeAIService.php` | **nuevo** (implements `AIServiceInterface`) |
| `src/Infrastructure/Services/VacationPlannerService.php` | reescritura parcial (llamada API + trait) |
| `src/Infrastructure/Controllers/PageController.php:577` | `new OpenAIService()` → `new ClaudeAIService()` |
| `src/Infrastructure/Services/OpenAIService.php` | **eliminar** |

## Fuera de alcance (YAGNI)

- No se toca `SmartAIService` (fallback sin IA).
- No se añade streaming (salidas < 16K).
- No se refactoriza la normalización de servicios más allá de moverla a `ClaudeAIService`.
- No se migran otros usos (no existen; grep confirma solo estos 2 servicios usan OpenAI).
