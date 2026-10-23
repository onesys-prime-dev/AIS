<?php

/**
 * Post-deploy smoke test — proves a real browser meets a working app, not
 * just that /health returns JSON. Checks the login page: it loads, serves
 * its built CSS, and carries a CSRF token (so the login form itself will
 * actually submit). Used by .github/workflows/deploy-reusable.yml.
 *
 * Usage: php .github/scripts/smoke-test.php <site_url> [--insecure]
 */

$siteUrl = $argv[1] ?? null;
$insecure = in_array('--insecure', $argv, true);

if (! $siteUrl) {
    fwrite(STDERR, "Usage: php smoke-test.php <site_url> [--insecure]\n");
    exit(1);
}

$siteUrl = rtrim($siteUrl, '/');

function fetch(string $url, bool $insecure, bool $followRedirects = true): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => $followRedirects,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => ! $insecure,
        CURLOPT_SSL_VERIFYHOST => $insecure ? 0 : 2,
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [$status, (string) $body, $error];
}

function check(string $label, bool $ok, string $detail = ''): void
{
    echo ($ok ? 'PASS' : 'FAIL') . " - {$label}" . ($detail !== '' ? " ({$detail})" : '') . "\n";
    if (! $ok) {
        exit(1);
    }
}

// 1) The login page itself loads with a 200.
[$status, $body, $error] = fetch("{$siteUrl}/login", $insecure);
check('Login page returns 200', $status === 200, $error !== '' ? $error : "status {$status}");

// 2) It actually rendered the login form, not an error page that happens to be a 200.
check('Login form is present', str_contains($body, 'name="email"') && str_contains($body, 'name="password"'));

// 3) A CSRF token is embedded — if this is missing the form can't submit.
check('CSRF token is present', (bool) preg_match('/name="csrf_test_name"\s+value="[a-f0-9]+"/', $body));

// 4) The built Tailwind CSS this page links actually resolves (not a 404 for
// a stale filemtime-cache-busted URL, or a build that never ran).
if (preg_match('/<link[^>]+href="([^"]*\/css\/main\.css[^"]*)"/', $body, $m)) {
    [$cssStatus] = fetch($m[1], $insecure);
    check('Built CSS loads', $cssStatus === 200, "status {$cssStatus}");
} else {
    check('Built CSS link is present', false);
}

// 5) Anonymous visitors to a protected area are sent to login, not shown it.
// Redirects must NOT be followed here, or this would just re-check the
// login page a second time and always "pass".
[$dashStatus, , ] = fetch("{$siteUrl}/client_portal/dashboard", $insecure, followRedirects: false);
check('Protected route redirects anonymous visitors', in_array($dashStatus, [302, 303], true), "status {$dashStatus}");

echo "Smoke test passed.\n";
