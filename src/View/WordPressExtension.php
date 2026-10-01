<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\View;

use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

final class WordPressExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('__', static fn (string $text): string => __($text, 'sympress-starter')),
            new TwigFunction('wp_head', fn (): Markup => $this->capture(wp_head(...))),
            new TwigFunction('wp_footer', fn (): Markup => $this->capture(wp_footer(...))),
            new TwigFunction('wp_body_open', fn (): Markup => $this->capture(wp_body_open(...))),
            new TwigFunction('language_attributes', fn (): Markup => $this->capture(language_attributes(...))),
            new TwigFunction('body_class', fn (): Markup => $this->capture(body_class(...))),
            new TwigFunction('comments', fn (): Markup => $this->capture(comments_template(...))),
        ];
    }

    private function capture(callable $callback): Markup
    {
        ob_start();
        try {
            $callback();
            return new Markup((string) ob_get_contents(), 'UTF-8');
        } finally {
            ob_end_clean();
        }
    }
}
