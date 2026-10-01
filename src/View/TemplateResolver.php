<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\View;

use SymPress\TwigBundle\Renderer\TemplateRendererInterface;

final class TemplateResolver
{
    /** @var list<string> */
    private array $hierarchy = [];
    private bool $registered = false;

    public function __construct(private readonly TemplateRendererInterface $templates)
    {
    }

    public function register(): void
    {
        if ($this->registered) {
            return;
        }
        $this->registered = true;
        foreach (['404', 'archive', 'attachment', 'author', 'category', 'date', 'embed', 'frontpage', 'home', 'index', 'page', 'paged', 'privacypolicy', 'search', 'single', 'singular', 'tag', 'taxonomy'] as $type) {
            add_filter($type . '_template_hierarchy', $this->capture(...), PHP_INT_MAX);
        }
        add_filter('theme_page_templates', $this->pageTemplates(...));
    }

    /**
     * Observe the hierarchy in the order WordPress evaluates it.
     *
     * @param list<string> $templates
     * @return list<string>
     */
    public function capture(array $templates): array
    {
        $this->hierarchy = array_values(array_unique([...$this->hierarchy, ...$this->normalize($templates)]));
        return $templates;
    }

    /**
     * @param array<string, string> $templates
     * @return array<string, string>
     */
    public function pageTemplates(array $templates): array
    {
        foreach (glob(get_template_directory() . '/resources/views/custom/*.html.twig') ?: [] as $file) {
            $headers = get_file_data($file, ['name' => 'Template Name']);
            if ($headers['name'] === '') {
                continue;
            }

            $templates['custom/' . basename($file)] = $headers['name'];
        }
        return $templates;
    }

    /** @param list<string> $candidates */
    public function resolve(array $candidates): string
    {
        foreach ([...$this->normalize($candidates), 'index'] as $candidate) {
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
        $filtered = apply_filters('sympress_starter/template_candidates', $this->hierarchy);
        return is_array($filtered) ? $this->normalize($filtered) : $this->hierarchy;
    }

    /**
     * @param array<mixed> $templates
     * @return list<string>
     */
    private function normalize(array $templates): array
    {
        $result = [];
        foreach ($templates as $template) {
            if (!is_string($template) || $template === '' || preg_match('~(^/|\\\\|\x00|:|@|(?:^|/)\.\.(?:/|$))~', $template)) {
                continue;
            }
            $name = preg_replace('/(?:\.html\.twig|\.php)$/', '', $template);
            if ($name === null || $name === '') {
                continue;
            }

            $result[] = $name;
        }
        return array_values(array_unique($result));
    }
}
