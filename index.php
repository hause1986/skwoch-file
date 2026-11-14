<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/php/bootstrap.php';
require_once __DIR__ . '/php/builder.php';

$configPath = __DIR__ . '/config.json';
$config     = loadConfig($configPath);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'build') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        echo json_encode(handleBuild($config, $configPath), JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$user = $config['user_defaults'];
require __DIR__ . '/templates/form.php';