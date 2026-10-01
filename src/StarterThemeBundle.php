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
        $container->registerForAutoconfiguration(View\ContextComposer::class)->addTag('sympress_starter.context_composer');
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('twig', [
            'paths' => [$this->path() . '/resources/views' => 'StarterTheme'],
        ]);
    }
}
