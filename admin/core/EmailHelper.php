<?php
// admin/core/EmailHelper.php — Email helper
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';

class EmailHelper
{
    public static function send(string $to, string $subject, string $body): bool
    {
        $headers = "From: noreply@your-domain.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        return mail($to, $subject, $body, $headers);
    }
}
