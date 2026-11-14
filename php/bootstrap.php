<?php
declare(strict_types=1);

/**
 * Загружает config.json и дополняет значениями по умолчанию.
 */
function loadConfig(string $path): array
{
    $config = is_file($path)
        ? json_decode((string)file_get_contents($path), true)
        : [];

    if (!is_array($config)) {
        $config = [];
    }

    $config = $config + [
        'output_filename'            => 'output',
        'output_extension'           => '.txt',
        'max_depth'                  => 50,
        'ignore_dirs'                => ['.git', 'node_modules', 'vendor'],
        'allowed_extensions_default' => ['php', 'js', 'css', 'txt', 'md', 'html'],
        'user_defaults'              => [],
    ];

    $config['user_defaults'] = $config['user_defaults'] + [
        'source_dir' => '',
        'output_dir' => '',
        'extensions' => $config['allowed_extensions_default'],
        'limit_kb'   => 100,
    ];

    return $config;
}

/**
 * Сохраняет пользовательские значения в config.json.
 * Пишет атомарно: сначала во временный файл, затем rename.
 */
function saveUserDefaults(string $configPath, array $config, array $userDefaults): array
{
    $config['user_defaults'] = $userDefaults;

    $json = json_encode(
        $config,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    if ($json === false) {
        throw new RuntimeException('Не удалось сериализовать конфигурацию.');
    }

    $tmp = $configPath . '.tmp';
    if (@file_put_contents($tmp, $json . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('Не удалось записать временный файл конфигурации.');
    }

    if (!@rename($tmp, $configPath)) {
        @unlink($tmp);
        throw new RuntimeException('Не удалось обновить config.json.');
    }

    return $config;
}

/**
 * Нормализует и валидирует пользовательские поля.
 */
function normalizeUserDefaults(array $config, array $input): array
{
    $allowed   = $config['allowed_extensions_default'];
    $extsIn    = $input['extensions'] ?? [];
    $exts      = [];
    if (is_array($extsIn)) {
        foreach ($extsIn as $e) {
            $e = strtolower(trim((string)$e));
            if ($e !== '' && in_array($e, $allowed, true)) {
                $exts[] = $e;
            }
        }
    }
    $exts = array_values(array_unique($exts));
    if (!$exts) {
        $exts = $allowed;
    }

    $limit = (int)($input['limit_kb'] ?? 100);
    if ($limit <= 0) {
        $limit = 100;
    }

    return [
        'source_dir' => trim((string)($input['source_dir'] ?? '')),
        'output_dir' => trim((string)($input['output_dir'] ?? '')),
        'extensions' => $exts,
        'limit_kb'   => $limit,
    ];
}