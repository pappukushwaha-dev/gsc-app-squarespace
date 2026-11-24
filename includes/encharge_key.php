<?php
/**
 * Encharge write key, read from this app's .env (2026-11-24).
 * The scripts set $ENCHARGE_WRITE_KEY before config.php loads, so the
 * .env file is read directly here instead of relying on Dotenv.
 * Usage: $ENCHARGE_WRITE_KEY = require __DIR__ . '/../includes/encharge_key.php';
 */
return (static function (): string {
    $v = getenv('ENCHARGE_WRITE_KEY');
    if (is_string($v) && $v !== '') {
        return $v;
    }
    if (!empty($_ENV['ENCHARGE_WRITE_KEY'])) {
        return (string) $_ENV['ENCHARGE_WRITE_KEY'];
    }
    $file = dirname(__DIR__) . '/.env';
    if (is_readable($file)) {
        foreach ((array) file($file, FILE_IGNORE_NEW_LINES) as $line) {
            if (preg_match('/^\s*ENCHARGE_WRITE_KEY\s*=\s*(.*)$/', $line, $m)) {
                return trim(trim($m[1]), "\"'");
            }
        }
    }
    error_log('ENCHARGE_WRITE_KEY missing in ' . $file);
    return '';
})();
