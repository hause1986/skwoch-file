<?php
declare(strict_types=1);

/**
 * Путь относительно базовой директории.
 */
function makeRelativePath(string $path, string $base): string
{
    $base = rtrim($base, "/\\");
    $path = str_replace('\\', '/', $path);
    $base = str_replace('\\', '/', $base);

    if (str_starts_with($path, $base . '/')) {
        return substr($path, strlen($base) + 1);
    }
    return $path;
}