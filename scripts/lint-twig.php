<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$loader = new Twig\Loader\FilesystemLoader();
$loader->addPath(dirname(__DIR__) . '/resources/views', 'theme');
$twig = new Twig\Environment($loader, ['strict_variables' => true]);
foreach (['TemplateRuntime', 'TranslationRuntime', 'EscapingRuntime', 'QueryRuntime'] as $runtime) {
    $twig->addExtension(new Twig\Extension\AttributeExtension('SymPress\\TwigBundle\\WordPress\\Runtime\\' . $runtime));
}
$twig->addNodeVisitor(new SymPress\TwigBundle\WordPress\Lint\NoRawFilter());
$count = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/resources/views')) as $file) {
    if (!$file->isFile() || !str_ends_with($file->getFilename(), '.twig')) {
        continue;
    }
    $name = '@theme/' . substr($file->getPathname(), strlen(dirname(__DIR__) . '/resources/views/'));
    $twig->compileSource($loader->getSourceContext($name));
    ++$count;
}
echo "Compiled {$count} Twig templates.\n";
