<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\Tests\Unit;

use Brain\Monkey\Filters;
use SymPress\StarterTheme\View\TemplateResolver;
use SymPress\TwigBundle\Renderer\TemplateRendererInterface;

final class TemplateResolverTest extends WordPressTestCase
{
    public function testCoreHierarchyAndFilterOrder(): void
    {
        $renderer = $this->createStub(TemplateRendererInterface::class);
        $resolver = new TemplateResolver($renderer);
        $resolver->register();
        $resolver->register();
        $core = ['category-news.php', 'category-7.php', 'category.php'];
        self::assertTrue(has_filter('category_template_hierarchy', $resolver->capture(...)) !== false);
        self::assertSame($core, $resolver->capture($core));
        $resolver->capture(['archive.php']);
        self::assertSame(['category-news', 'category-7', 'category', 'archive'], $resolver->candidates());
    }

    public function testSearchAnd404CanBeOverriddenAndInvalidValuesCannotEscapeNamespace(): void
    {
        foreach (['search', '404'] as $type) {
            $resolver = new TemplateResolver($this->createStub(TemplateRendererInterface::class));
            $resolver->capture([$type . '.php']);
            Filters\expectApplied('sympress_starter/template_candidates')->once()->with([$type])->andReturn(['custom/result.html.twig', '../secret', '/absolute', 42, $type]);
            self::assertSame(['custom/result', $type], $resolver->candidates());
            Filters\expectApplied('sympress_starter/template_candidates')->once()->with([$type])->andReturn(false);
            self::assertSame([$type], $resolver->candidates());
        }
    }

    public function testNativeNamesAndCustomPagePathsResolve(): void
    {
        $renderer = $this->createStub(TemplateRendererInterface::class);
        $renderer->method('exists')->willReturnCallback(static fn (string $name): bool => $name === '@StarterTheme/custom/landing.html.twig');
        $resolver = new TemplateResolver($renderer);
        $resolver->capture(['custom/landing.html.twig', 'page-ueber.php', 'page-12.php', 'page.php']);
        self::assertSame('@StarterTheme/custom/landing.html.twig', $resolver->resolve($resolver->candidates()));
    }

    public function testIndexFallbackAndMissingTemplateError(): void
    {
        $renderer = $this->createStub(TemplateRendererInterface::class);
        $renderer->method('exists')->willReturnCallback(static fn (string $name): bool => $name === '@StarterTheme/index.html.twig');
        self::assertSame('@StarterTheme/index.html.twig', (new TemplateResolver($renderer))->resolve(['missing']));
        $this->expectException(\RuntimeException::class);
        (new TemplateResolver($this->createStub(TemplateRendererInterface::class)))->resolve(['missing']);
    }
}
