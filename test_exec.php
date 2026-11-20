<?php
header('Content-Type: application/json');

$output = [];
$output['uname'] = php_uname();
$output['user'] = get_current_user();
$output['exec_enabled'] = function_exists('exec');
$output['shell_exec_enabled'] = function_exists('shell_exec');

// Test if curl can download
$nodeUrl = 'https://nodejs.org/dist/v20.17.0/node-v20.17.0-linux-x64.tar.gz';
$output['can_curl'] = function_exists('curl_init');

echo json_encode($output, JSON_PRETTY_PRINT);
?>
