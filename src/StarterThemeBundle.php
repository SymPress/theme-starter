<?php

declare(strict_types=1);

namespace SymPress\StarterTheme;

use SymPress\Kernel\Bundle\AbstractBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

final class StarterThemeBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('sympress_twig', [
            'wordpress' => ['themes' => ['sympress-starter' => []]],
        ]);
    }

    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $container->register(WordPress\Theme::class)->setPublic(true);
    }
}
