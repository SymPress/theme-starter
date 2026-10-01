<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\View;

use SymPress\TwigBundle\Renderer\TemplateRendererInterface;

final readonly class TemplateResolver
{
    public function __construct(private TemplateRendererInterface $templates)
    {
    }

    /** @param list<string> $candidates */
    public function resolve(array $candidates): string
    {
        foreach ([...$candidates, 'index'] as $candidate) {
            $template = '@StarterTheme/' . $candidate . '.html.twig';
            if ($this->templates->exists($template)) {
                return $template;
            }
        }

        throw new \RuntimeException('The StarterTheme index.html.twig template is missing.');
    }

    /** @return list<string> */
    public function candidates(): array
    {
        if (is_404()) {
            return ['404'];
        }
        if (is_search()) {
            return ['search'];
        }

        $candidates = [];
        if (is_front_page()) {
            $candidates[] = 'front-page';
        }
        if (is_home()) {
            $candidates[] = 'home';
        } elseif (is_page()) {
            $candidates = [...$candidates, 'page-' . get_queried_object_id(), 'page', 'singular'];
        } elseif (is_singular()) {
            $candidates = [...$candidates, 'single-' . get_post_type(), 'single', 'singular'];
        } elseif (is_archive()) {
            $candidates[] = 'archive';
        }

        /** @var list<string> $candidates */
        $candidates = apply_filters('sympress_starter/template_candidates', $candidates);

        return $candidates;
    }
}
