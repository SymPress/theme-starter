<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$loader = new Twig\Loader\FilesystemLoader();
$loader->addPath(dirname(__DIR__) . '/resources/views', 'StarterTheme');
$twig = new Twig\Environment($loader, ['strict_variables' => true]);
$twig->addExtension(new SymPress\StarterTheme\View\WordPressExtension());
$count = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/resources/views')) as $file) {
    if (!$file->isFile() || !str_ends_with($file->getFilename(), '.twig')) {
        continue;
    }
    $name = '@StarterTheme/' . substr($file->getPathname(), strlen(dirname(__DIR__) . '/resources/views/'));
    $twig->parse($twig->tokenize($loader->getSourceContext($name)));
    ++$count;
}
echo "Parsed {$count} Twig templates.\n";
