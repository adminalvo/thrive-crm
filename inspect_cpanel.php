<?php
header('Content-Type: application/json');

$dir = __DIR__;
$items = scandir($dir);
$result = [];

foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $path = $dir . '/' . $item;
    $result[] = [
        'name' => $item,
        'is_dir' => is_dir($path),
        'size' => is_file($path) ? filesize($path) : null,
        'mtime' => date('Y-m-d H:i:s', filemtime($path))
    ];
}

// Also check node process status
$pidFile = $dir . '/node.pid';
$runningPid = file_exists($pidFile) ? trim(file_get_contents($pidFile)) : null;
$psCheck = $runningPid ? shell_exec("ps -p $runningPid -o pid,stat,cmd") : null;

echo json_encode([
    'dir' => $dir,
    'files' => $result,
    'running_pid' => $runningPid,
    'ps' => $psCheck
], JSON_PRETTY_PRINT);
?>
