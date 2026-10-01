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

    public function render(): string
    {
        $template = $this->resolver->resolve($this->resolver->candidates());

        return $this->templates->render($template, $this->context->build());
    }
}
