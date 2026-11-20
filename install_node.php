<?php
header('Content-Type: application/json');
set_time_limit(300);

$baseDir = __DIR__;
$binDir = $baseDir . '/bin';
if (!file_exists($binDir)) {
  mkdir($binDir, 0755, true);
}

$output = [];
$nodeBin = $binDir . '/node';

if (file_exists($nodeBin)) {
  $output['status'] = 'already_installed';
  $output['node_v'] = trim(shell_exec("$nodeBin -v 2>&1"));
  echo json_encode($output, JSON_PRETTY_PRINT);
  exit;
}

// Download node binary directly from unpkg or official nodejs tar
// A smaller, faster way: download node binary directly or download node tar.gz
$tarFile = $baseDir . '/node.tar.gz';
$nodeUrl = 'https://nodejs.org/dist/v20.17.0/node-v20.17.0-linux-x64.tar.gz';

$output['downloading'] = $nodeUrl;
$fp = fopen($tarFile, 'w+');
$ch = curl_init($nodeUrl);
curl_setopt($ch, CURLOPT_TIMEOUT, 180);
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$exec = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
fclose($fp);

$output['download_status'] = $httpCode;
$output['tar_size'] = file_exists($tarFile) ? filesize($tarFile) : 0;

if ($httpCode === 200 && $output['tar_size'] > 1000000) {
  // Extract ONLY the bin/node binary from the tarball
  $cmd = "tar -xzf " . escapeshellarg($tarFile) . " --strip-components=2 -C " . escapeshellarg($binDir) . " node-v20.17.0-linux-x64/bin/node 2>&1";
  $output['extract_cmd'] = $cmd;
  $output['extract_result'] = shell_exec($cmd);

  // Set executable permission
  if (file_exists($nodeBin)) {
    chmod($nodeBin, 0755);
    $output['installed'] = true;
    $output['node_version'] = trim(shell_exec("$nodeBin -v 2>&1"));
  } else {
    $output['installed'] = false;
    $output['error'] = 'Node binary not found after extract';
  }

  // Clean up tar.gz to save disk space
  @unlink($tarFile);
} else {
  $output['error'] = 'Download failed or file too small';
}

echo json_encode($output, JSON_PRETTY_PRINT);
?>
