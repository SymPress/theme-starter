<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\View;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class WordPressExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('__', static fn (string $text): string => __($text, 'sympress-starter')),
            new TwigFunction('_x', static fn (string $text, string $context): string => _x($text, $context, 'sympress-starter')),
            new TwigFunction('_n', static fn (string $single, string $plural, int $number): string => _n($single, $plural, $number, 'sympress-starter')),
            new TwigFunction('sprintf', sprintf(...)),
            new TwigFunction('menu', static fn (string $location, int $depth = 2): string => (string) wp_nav_menu([
                'theme_location' => $location,
                'depth'          => $depth,
                'echo'           => false,
                'container'      => '',
                'fallback_cb'    => false,
            ]), ['is_safe' => ['html']]),
            new TwigFunction('wp_head', fn (): string => $this->capture(wp_head(...)), ['is_safe' => ['html']]),
            new TwigFunction('wp_footer', fn (): string => $this->capture(wp_footer(...)), ['is_safe' => ['html']]),
            new TwigFunction('wp_body_open', fn (): string => $this->capture(wp_body_open(...)), ['is_safe' => ['html']]),
            new TwigFunction('language_attributes', fn (): string => $this->capture(language_attributes(...)), ['is_safe' => ['html']]),
            new TwigFunction('body_class', fn (): string => $this->capture(body_class(...)), ['is_safe' => ['html']]),
            new TwigFunction('comments', fn (): string => $this->capture(comments_template(...)), ['is_safe' => ['html']]),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('esc_html', static fn (string $value): string => esc_html($value), ['is_safe' => ['html']]),
            new TwigFilter('esc_attr', static fn (string $value): string => esc_attr($value), ['is_safe' => ['html']]),
            new TwigFilter('esc_url', static fn (string $value): string => esc_url($value), ['is_safe' => ['html']]),
        ];
    }

    private function capture(callable $callback): string
    {
        ob_start();
        try {
            $callback();
            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
