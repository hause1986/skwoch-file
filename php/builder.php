<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/bootstrap.php';

function handleBuild(array $config, string $configPath): array
{
    $sourceDir  = rtrim(trim((string)($_POST['source_dir'] ?? '')), "/\\");
    $outputDir  = rtrim(trim((string)($_POST['output_dir'] ?? '')), "/\\");
    $extensions = $_POST['extensions'] ?? [];
    $limitKb    = (int)($_POST['limit_kb'] ?? 0);

    if ($sourceDir === '' || !is_dir($sourceDir)) {
        throw new RuntimeException('Исходная папка не найдена или не является директорией.');
    }
    if ($outputDir === '') {
        throw new RuntimeException('Не указана выходная папка.');
    }
    if (!is_dir($outputDir)) {
        if (!@mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
            throw new RuntimeException('Не удалось создать выходную папку: ' . $outputDir);
        }
    }
    if (!is_writable($outputDir)) {
        throw new RuntimeException('Нет прав на запись в выходную папку: ' . $outputDir);
    }
    if (!is_array($extensions) || count($extensions) === 0) {
        throw new RuntimeException('Не выбран ни один тип файлов.');
    }
    if ($limitKb <= 0) {
        throw new RuntimeException('Размер (в КБ) должен быть больше нуля.');
    }

    $extensions = array_map(static fn($e) => strtolower(ltrim(trim((string)$e), '.')), $extensions);
    $extensions = array_values(array_filter($extensions, static fn($e) => $e !== ''));
    if (!$extensions) {
        throw new RuntimeException('Некорректный список расширений.');
    }

    $limitBytes = $limitKb * 1024;
    $baseName   = $config['output_filename'];
    $outExt     = $config['output_extension'];

    $files = [];
    $iter  = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $sourceDir,
            FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
        ),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $ignore = array_map('strtolower', $config['ignore_dirs'] ?? []);
    foreach ($iter as $fileInfo) {
        if (!$fileInfo->isFile()) { continue; }
        $pathLower = strtolower($fileInfo->getPathname());
        $skip = false;
        foreach ($ignore as $ig) {
            if ($ig !== '' && str_contains($pathLower, DIRECTORY_SEPARATOR . $ig . DIRECTORY_SEPARATOR)) {
                $skip = true; break;
            }
        }
        if ($skip) { continue; }
        if (!in_array(strtolower($fileInfo->getExtension()), $extensions, true)) { continue; }
        $files[] = $fileInfo->getPathname();
    }

    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    // ---------- Сохраняем настройки пользователя ----------
    // Сохраняем даже если файлов не найдено — пользователь всё равно ввёл валидные данные.
    $configSaveWarning = null;
    try {
        saveUserDefaults($configPath, $config, normalizeUserDefaults($config, $_POST));
    } catch (Throwable $e) {
        $configSaveWarning = $e->getMessage();
    }

    if (!$files) {
        $result = [
            'ok'      => true,
            'written' => [],
            'skipped' => 'Не найдено файлов с указанными расширениями.',
            'config_saved' => $configSaveWarning === null,
        ];
        if ($configSaveWarning !== null) {
            $result['config_warning'] = $configSaveWarning;
        }
        return $result;
    }

    $written = []; $partIndex = 1; $currentBytes = 0;
    $handle = null; $currentPath = null;

    $openNew = static function () use (&$handle, &$currentPath, &$partIndex, &$currentBytes, $outputDir, $baseName, $outExt) {
        if ($handle) { fclose($handle); }
        $currentPath  = $outputDir . DIRECTORY_SEPARATOR . $baseName . '_' . $partIndex . $outExt;
        $handle       = fopen($currentPath, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Не удалось создать файл: ' . $currentPath);
        }
        $currentBytes = 0;
        $partIndex++;
    };
    $openNew();

    foreach ($files as $path) {
        $rel     = makeRelativePath($path, $sourceDir);
        $content = @file_get_contents($path);
        if ($content === false) { $content = '<<НЕ УДАЛОСЬ ПРОЧИТАТЬ ФАЙЛ>>'; }

        $block      = "===== " . $rel . " =====\n" . $content . "\n\n";
        $blockBytes = strlen($block);

        if ($currentBytes > 0 && ($currentBytes + $blockBytes) > $limitBytes) {
            $written[] = $currentPath;
            $openNew();
        }
        fwrite($handle, $block);
        $currentBytes += $blockBytes;
        if ($currentBytes >= $limitBytes) {
            $written[] = $currentPath;
            $openNew();
        }
    }

    if ($handle) {
        $size = ftell($handle);
        fclose($handle);
        $handle = null;
        if ($size > 0) { $written[] = $currentPath; }
        else { @unlink($currentPath); }
    }

    $written = array_values(array_unique($written));

    $result = [
        'ok'      => true,
        'written' => array_map(static fn($p) => makeRelativePath($p, $outputDir), $written),
        'count'   => count($written),
        'files'   => count($files),
        'config_saved' => $configSaveWarning === null,
    ];
    if ($configSaveWarning !== null) {
        $result['config_warning'] = $configSaveWarning;
    }
    return $result;
}