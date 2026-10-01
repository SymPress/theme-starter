<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\Tests\Unit;

use Brain\Monkey\Functions;
use SymPress\StarterTheme\View\Renderer;
use SymPress\StarterTheme\View\TemplateResolver;
use SymPress\StarterTheme\WordPress\Context;
use SymPress\TwigBundle\Renderer\TemplateRendererInterface;

final class RendererTest extends WordPressTestCase
{
    public function testExplicitRenderingDoesNotReadTheWordPressLoop(): void
    {
        Functions\expect('have_posts')->never();
        $templates = $this->createMock(TemplateRendererInterface::class);
        $templates->method('exists')->willReturn(true);
        $templates->expects(self::once())->method('render')
            ->with('@StarterTheme/partials/post-summary.html.twig', ['post' => ['title' => 'REST data']])
            ->willReturn('<article>REST data</article>');
        $renderer = new Renderer($templates, new TemplateResolver($templates), new Context());
        self::assertSame('<article>REST data</article>', $renderer->render(['partials/post-summary'], ['post' => ['title' => 'REST data']]));
    }
}
