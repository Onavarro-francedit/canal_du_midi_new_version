<?php
// src/Infrastructure/Services/Csrf.php
namespace App\Infrastructure\Services;

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function check(?string $submitted): bool
    {
        if (empty($_SESSION['csrf_token']) || $submitted === null || $submitted === '') {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $submitted);
    }
}
