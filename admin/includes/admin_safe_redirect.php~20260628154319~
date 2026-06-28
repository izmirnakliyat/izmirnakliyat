<?php
declare(strict_types=1);

/**
 * login.php / 2FA sonrası güvenli admin içi yönlendirme.
 * /admin/mynak_db_clean.php?run=1 → mynak_db_clean.php?run=1
 */
function admin_login_safe_redirect(?string $raw): string
{
    $raw = trim((string) $raw);
    if ($raw === '') {
        return 'dashboard.php';
    }
    if (preg_match('#^[a-z]+://#i', $raw)) {
        return 'dashboard.php';
    }

    if (preg_match('#/admin/([^?\#]+(?:\?[^\#]*)?)#i', $raw, $m)) {
        $raw = $m[1];
    }

    if (strpbrk($raw, "/\\") !== false) {
        return 'dashboard.php';
    }

    $pathPart = $raw;
    $queryPart = '';
    if (str_contains($raw, '?')) {
        [$pathPart, $queryPart] = explode('?', $raw, 2);
    }

    $base = basename($pathPart);
    if (!preg_match('/^[a-zA-Z0-9_-]+\.php$/', $base)) {
        return 'dashboard.php';
    }

    return $queryPart !== '' ? ($base . '?' . $queryPart) : $base;
}

/** Oturum yokken login.php?redirect=… için admin-göreli hedef (örn. mynak_db_clean.php?run=1). */
function admin_login_redirect_param_from_request_uri(?string $uri): string
{
    $uri = trim((string) $uri);
    if ($uri === '') {
        return '';
    }
    if (preg_match('#/admin/([^?\#]+(?:\?[^\#]*)?)#i', $uri, $m)) {
        return $m[1];
    }

    return $uri;
}
