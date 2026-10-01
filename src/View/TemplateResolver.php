<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\View;

use SymPress\TwigBundle\Renderer\TemplateRendererInterface;

final class TemplateResolver
{
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
        add_filter('theme_page_templates', $this->pageTemplates(...));
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

    /**
     * Explicit candidates support REST/AJAX/CLI renderers without a main query.
     *
     * @param list<string>|null $explicit
     * @return list<string>
     */
    public function candidates(?array $explicit = null): array
    {
        $hierarchy = $explicit === null ? $this->currentHierarchy() : $this->normalize($explicit);
        $filtered = apply_filters('sympress_starter/template_candidates', $hierarchy);
        return is_array($filtered) ? $this->normalize($filtered) : $hierarchy;
    }

    /** @return list<string> */
    private function currentHierarchy(): array
    {
        $hierarchy = [];
        $capture = static function (array $templates) use (&$hierarchy): array {
            $hierarchy = [...$hierarchy, ...$templates];
            return $templates;
        };
        $types = ['404', 'archive', 'attachment', 'author', 'category', 'date', 'embed', 'frontpage', 'home', 'index', 'page', 'privacypolicy', 'search', 'single', 'singular', 'tag', 'taxonomy'];
        foreach ($types as $type) {
            add_filter($type . '_template_hierarchy', $capture, PHP_INT_MAX);
        }
        try {
            // Same conditional order as WordPress's template-loader.php. Each call
            // derives names from the current query and applies native hierarchy filters.
            $getters = [
                'is_embed'             => 'get_embed_template',
                'is_404'               => 'get_404_template',
                'is_search'            => 'get_search_template',
                'is_front_page'        => 'get_front_page_template',
                'is_home'              => 'get_home_template',
                'is_privacy_policy'    => 'get_privacy_policy_template',
                'is_post_type_archive' => 'get_archive_template',
                'is_tax'               => 'get_taxonomy_template',
                'is_attachment'        => 'get_attachment_template',
                'is_single'            => 'get_single_template',
                'is_page'              => 'get_page_template',
                'is_singular'          => 'get_singular_template',
                'is_category'          => 'get_category_template',
                'is_tag'               => 'get_tag_template',
                'is_author'            => 'get_author_template',
                'is_date'              => 'get_date_template',
                'is_archive'           => 'get_archive_template',
            ];
            foreach ($getters as $condition => $getter) {
                if ($condition() && $getter()) {
                    break;
                }
            }
            if ($hierarchy === []) {
                get_index_template();
            }
        } finally {
            foreach ($types as $type) {
                remove_filter($type . '_template_hierarchy', $capture, PHP_INT_MAX);
            }
        }
        return $this->normalize($hierarchy);
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
