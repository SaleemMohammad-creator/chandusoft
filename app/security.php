<?php
// app/security.php
// include this at top of public pages and admin pages after config.php

// Force HTTPS in non-local environment
if ((($_ENV['APP_ENV'] ?? 'local') !== 'local') && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) {
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    header("Location: https://{$host}{$uri}", true, 301);
    exit;
}

// Security headers
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer-when-downgrade");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("Content-Security-Policy: default-src 'self' https: 'unsafe-inline'; img-src 'self' data: https:;");

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'use_strict_mode' => true,
        'cookie_samesite' => 'Lax',
    ]);
}
