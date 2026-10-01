<?php

declare(strict_types=1);

namespace SymPress\StarterTheme;

use SymPress\Kernel\Bundle\AbstractBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

final class StarterThemeBundle extends AbstractBundle
{
    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('twig', [
            'paths' => [$this->path() . '/templates' => 'StarterTheme'],
        ]);
    }
}
