<?php
$output = [];
$output['alt_nodejs'] = @shell_exec('ls -la /opt/alt/alt-nodejs* /opt/alt/alt-nodejs*/root/usr/bin/node 2>&1');
$output['cl_selector'] = @shell_exec('ls -la /opt/alt/ 2>&1');
$output['cloudlinux'] = @shell_exec('ls -la /usr/share/cagefs* /etc/cagefs 2>&1');
$output['node_find'] = @shell_exec('find /opt /usr -name "node" -type f 2>/dev/null');
echo json_encode($output, JSON_PRETTY_PRINT);
?>