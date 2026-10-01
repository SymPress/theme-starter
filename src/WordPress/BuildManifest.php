<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\WordPress;

final class BuildManifest
{
    /** @return array<string, array<string, list<string>>>|null */
    public static function read(string $file): ?array
    {
        if (!is_readable($file)) {
            return null;
        }
        $contents = file_get_contents($file);
        if ($contents === false) {
            return null;
        }
        try {
            $manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
        $entries = is_array($manifest) ? ($manifest['entrypoints'] ?? null) : null;
        if (!is_array($entries) || !isset($entries['sympress-starter-app']['css'], $entries['sympress-starter-editor']['css'])) {
            return null;
        }
        foreach ($entries as $name => $entry) {
            if (!is_string($name) || !is_array($entry)) {
                return null;
            }
            foreach ($entry as $type => $files) {
                if (!in_array($type, ['css', 'js'], true) || !self::validFiles($files, dirname($file))) {
                    return null;
                }
            }
        }
        return $entries;
    }

    private static function validFiles(mixed $files, string $directory): bool
    {
        if (!is_array($files) || !array_is_list($files) || $files === []) {
            return false;
        }
        foreach ($files as $asset) {
            if (
                !is_string($asset) || !preg_match('~^(?:\./)?[a-zA-Z0-9_.-]+\.(?:css|js)$~', $asset)
                || !is_readable($directory . '/' . basename($asset))
            ) {
                return false;
            }
        }
        return true;
    }
}
