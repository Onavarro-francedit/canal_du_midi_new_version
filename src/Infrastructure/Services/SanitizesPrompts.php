<?php
namespace App\Infrastructure\Services;

/**
 * Sanea el input del usuario antes de incluirlo en un prompt de IA.
 * Compartido por ClaudeAIService y VacationPlannerService (SEC-002/SEC-010).
 */
trait SanitizesPrompts
{
    /**
     * - recorta espacios, colapsa saltos de línea múltiples a uno,
     * - elimina la secuencia delimitadora para evitar inyección,
     * - trunca a $maxLen caracteres (multibyte).
     */
    protected function sanitizeUserPrompt(string $raw, int $maxLen): string
    {
        $clean = trim($raw);
        $clean = preg_replace('/\R{2,}/u', "\n", $clean) ?? $clean;
        $clean = str_replace('DEMANDE_UTILISATEUR', '', $clean);
        return mb_substr($clean, 0, $maxLen, 'UTF-8');
    }
}
