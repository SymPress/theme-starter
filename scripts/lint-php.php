<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    static function (SplFileInfo $file): bool {
        return !$file->isDir() || !in_array($file->getFilename(), ['vendor', 'node_modules', 'public', 'var', '.git'], true);
    },
));
$count = 0;
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $output, $status);
    if ($status !== 0) {
        throw new RuntimeException(implode("\n", $output));
    }
    $output = [];
    ++$count;
}
echo "PHP syntax checked: {$count} files.\n";
