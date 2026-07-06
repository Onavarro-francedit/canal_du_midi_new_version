<?php
// Test CLI del trait SanitizesPrompts (sin dependencias de API ni config).
require __DIR__ . '/../autoload.php';

use App\Infrastructure\Services\SanitizesPrompts;

$subject = new class {
    use SanitizesPrompts;
    public function run(string $raw, int $maxLen): string {
        return $this->sanitizeUserPrompt($raw, $maxLen);
    }
};

$fail = 0;
function check(bool $cond, string $name): void {
    global $fail;
    if ($cond) { echo "PASS: $name\n"; }
    else { echo "FAIL: $name\n"; $GLOBALS['fail'] = 1; }
}

// 1. Recorta espacios
check($subject->run('  hola  ', 100) === 'hola', 'trim espacios');
// 2. Colapsa saltos múltiples a uno
check($subject->run("a\n\n\n\nb", 100) === "a\nb", 'colapsa saltos');
// 3. Elimina la secuencia delimitadora
check(!str_contains($subject->run('DEMANDE_UTILISATEUR malicioso', 100), 'DEMANDE_UTILISATEUR'), 'elimina delimitador');
// 4. Trunca multibyte a maxLen
check(mb_strlen($subject->run(str_repeat('é', 50), 10), 'UTF-8') === 10, 'trunca multibyte');

exit($fail);
