<?php
/**
 * Debug: testa o fluxo de login com cookie jar para entender
 * por que o PHPUnit não está conseguindo manter a sessão.
 */

$jar = tempnam(sys_get_temp_dir(), 'akti_debug_');
$baseUrl = 'http://localhost/teste.akti.com';
$email = 'admin@sistema.com';
$password = 'admin123';

echo "Temp dir: " . sys_get_temp_dir() . PHP_EOL;
echo "Cookie jar: {$jar}" . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;

// ── Step 0: GET login page to obtain tenant_key and session cookie ──
echo "\n[0] GET login page first (to get tenant_key and session)...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $baseUrl . '/?page=login',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_COOKIEJAR      => $jar,
    CURLOPT_COOKIEFILE     => $jar,
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$body = substr($resp, $headerSize);
$headers = substr($resp, 0, $headerSize);

echo "HTTP code: {$code}" . PHP_EOL;
echo "Headers (Set-Cookie lines):" . PHP_EOL;
foreach (explode("\r\n", $headers) as $line) {
    if (stripos($line, 'Set-Cookie') !== false) {
        echo "  {$line}" . PHP_EOL;
    }
}

// Extract tenant_key from hidden input
$tenantKey = '';
if (preg_match('/name="tenant_key"\s+value="([^"]*)"/', $body, $m)) {
    $tenantKey = $m[1];
}
echo "Tenant key found in form: '{$tenantKey}'" . PHP_EOL;

echo "\nCookie jar after GET:" . PHP_EOL;
echo file_get_contents($jar) . PHP_EOL;

// ── Step 1: POST login with tenant_key ────────────────────────
echo str_repeat('=', 60) . PHP_EOL;
echo "\n[1] POST login with tenant_key='{$tenantKey}'...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $baseUrl . '/?page=login',
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'email' => $email,
        'password' => $password,
        'tenant_key' => $tenantKey,
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_COOKIEJAR      => $jar,
    CURLOPT_COOKIEFILE     => $jar,
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$err = curl_error($ch);
curl_close($ch);

$headers = substr($resp, 0, $headerSize);
$body = substr($resp, $headerSize);

echo "HTTP code: {$code}" . PHP_EOL;
echo "cURL error: {$err}" . PHP_EOL;
echo "Response headers:" . PHP_EOL;
foreach (explode("\r\n", $headers) as $line) {
    if (trim($line)) echo "  {$line}" . PHP_EOL;
}
echo "Body (first 200 chars): " . substr($body, 0, 200) . PHP_EOL;

echo "\nCookie jar after POST:" . PHP_EOL;
echo file_get_contents($jar) . PHP_EOL;

// ── Step 2: GET home page with cookie jar ─────────────────────
echo str_repeat('=', 60) . PHP_EOL;
echo "\n[2] GET home page with cookie jar...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $baseUrl . '/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_COOKIEJAR      => $jar,
    CURLOPT_COOKIEFILE     => $jar,
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

$body = substr($resp, $headerSize);

echo "HTTP code: {$code}" . PHP_EOL;
echo "Final URL: {$finalUrl}" . PHP_EOL;
echo "Body title: ";
if (preg_match('/<title>(.*?)<\/title>/i', $body, $m)) {
    echo $m[1];
} else {
    echo "(no title found)";
}
echo PHP_EOL;
echo "Is login page: " . (stripos($body, '<title>Login') !== false ? 'YES' : 'NO') . PHP_EOL;
echo "Has sidebar: " . (stripos($body, 'sidebar') !== false ? 'YES' : 'NO') . PHP_EOL;

// Cleanup
@unlink($jar);
echo "\nDone.\n";
