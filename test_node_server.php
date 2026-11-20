<?php
header('Content-Type: application/json');

$baseDir = __DIR__;
$nodeBin = $baseDir . '/bin/node';
$testServer = $baseDir . '/test_server.js';
$pidFile = $baseDir . '/node.pid';
$logFile = $baseDir . '/node.log';
$port = 3005;

// Create test Node.js HTTP server
$nodeCode = <<< 'NODE'
const http = require('http');
const port = 3005;

const server = http.createServer((req, res) => {
  res.writeHead(200, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify({
    status: 'ok',
    message: 'Hello from Node.js on cPanel!',
    node_version: process.version,
    uptime: process.uptime(),
    time: new Date().toISOString()
  }));
});

server.listen(port, '127.0.0.1', () => {
  console.log(`Test server running at http://127.0.0.1:${port}/`);
});
NODE;

file_put_contents($testServer, $nodeCode);

// Start node in background
$cmd = "nohup $nodeBin $testServer > $logFile 2>&1 & echo $!";
$pid = trim(shell_exec($cmd));
file_put_contents($pidFile, $pid);

// Wait 1 second and test local curl
sleep(1);

$ch = curl_init("http://127.0.0.1:$port/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 3);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

echo json_encode([
  'started_pid' => $pid,
  'port' => $port,
  'http_code' => $httpCode,
  'curl_error' => $err,
  'response' => json_decode($resp, true) ?: $resp,
  'log' => file_exists($logFile) ? file_get_contents($logFile) : null
], JSON_PRETTY_PRINT);
?>
