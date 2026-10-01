<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\View;

use SymPress\StarterTheme\WordPress\Context;
use SymPress\TwigBundle\Renderer\TemplateRendererInterface;

final readonly class Renderer
{
    public function __construct(
        private TemplateRendererInterface $templates,
        private TemplateResolver $resolver,
        private Context $context,
    ) {
    }

    /**
     * @param list<string>|null $candidates
     * @param array<string, mixed>|null $context
     */
    public function render(?array $candidates = null, ?array $context = null): string
    {
        $template = $this->resolver->resolve($this->resolver->candidates($candidates));

        return $this->templates->render($template, $context ?? $this->context->build($template));
    }
}
