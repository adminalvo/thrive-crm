<?php
// Transparent Reverse Proxy for Next.js CRM on Vercel
$targetHost = 'thrive-crm-theta.vercel.app';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$url = "https://{$targetHost}{$requestUri}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_ENCODING, ''); // Auto decode gzip/brotli from target

// Forward request headers
$reqHeaders = [];
$incoming = function_exists('getallheaders') ? getallheaders() : [];
foreach ($incoming as $name => $val) {
    $lower = strtolower($name);
    if (in_array($lower, ['host', 'connection', 'accept-encoding'])) continue;
    if ($lower === 'content-length' && $method === 'GET') continue;
    $reqHeaders[] = "{$name}: {$val}";
}
$reqHeaders[] = "Host: {$targetHost}";
$reqHeaders[] = "X-Forwarded-Host: crm.thrive.az";
$reqHeaders[] = "X-Forwarded-Proto: " . ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
$reqHeaders[] = "X-Real-IP: " . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaders);

if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
    $body = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
}

$response = curl_exec($ch);

if ($response === false) {
    http_response_code(502);
    echo "Gateway Error: " . curl_error($ch);
    exit;
}

$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$headerText = substr($response, 0, $headerSize);
$bodyText = substr($response, $headerSize);

http_response_code($httpCode);

// Send response headers
$lines = preg_split("/\r\n|\n|\r/", $headerText);
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || stripos($line, 'HTTP/') === 0) continue;
    // Don't forward transfer/content encoding headers because curl already decoded the body
    if (preg_match('/^(Transfer-Encoding|Content-Encoding|Connection):/i', $line)) continue;
    
    // Rewrite redirects to stay on crm.thrive.az
    if (preg_match('/^Location:\s*(https?:\/\/[^\/]+)?(.*)$/i', $line, $m)) {
        $loc = !empty($m[2]) ? $m[2] : '/';
        header("Location: {$loc}", false);
        continue;
    }
    
    header($line, false);
}

echo $bodyText;
