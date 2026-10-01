<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\WordPress;

use SymPress\StarterTheme\View\ContextComposer;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class Context
{
    /** @param iterable<ContextComposer> $composers */
    public function __construct(
        #[AutowireIterator('sympress_starter.context_composer')]
        private readonly iterable $composers = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function build(string $template = '@StarterTheme/index.html.twig'): array
    {
        $posts = [];
        while (have_posts()) {
            the_post();
            $post = get_post();
            if (!$post instanceof \WP_Post) {
                continue;
            }
            $protected = post_password_required();
            $categories = get_the_category();
            $content = '';
            if (is_singular()) {
                $content = $protected
                    ? get_the_password_form()
                    : str_replace(']]>', ']]&gt;', (string) apply_filters('the_content', get_the_content()));
            }
            $posts[] = [
                'id'        => get_the_ID(),
                'title'     => Content::text(get_the_title()),
                'url'       => sanitize_url((string) get_permalink()),
                'date'      => get_the_date(),
                'date_iso'  => get_the_date(DATE_W3C),
                'category'  => $protected ? '' : Content::text($categories[0]->name ?? ''),
                'excerpt'   => $protected ? __('Dieser Beitrag ist passwortgeschützt.', 'sympress-starter') : (is_singular() ? '' : Content::excerpt($post)),
                'image'     => Content::html($protected ? '' : get_the_post_thumbnail(null, 'large', ['class' => 'featured-image'])),
                'content'   => Content::html((string) $content),
                'pages'     => Content::html($protected ? '' : (string) wp_link_pages(['echo' => false])),
                'show_meta' => get_post_type() === 'post',
            ];
        }
        wp_reset_postdata();

        $homeTitle = get_option('page_for_posts') ? get_the_title((int) get_option('page_for_posts')) : get_bloginfo('name');
        $title = match (true) {
            is_404() => __('Diese Seite fehlt.', 'sympress-starter'),
            is_search() => sprintf(__('Suche nach „%s“', 'sympress-starter'), get_search_query(false)),
            is_archive() => Content::text(get_the_archive_title()),
            default => Content::text((string) $homeTitle),
        };

        $context = [
            'site'          => [
                'name'        => Content::text(get_bloginfo('name')),
                'description' => Content::text(get_bloginfo('description')),
                'url'         => sanitize_url(home_url('/')),
                'charset'     => get_bloginfo('charset'),
                'logo'        => Content::html(get_custom_logo()),
            ],
            'title'         => $title,
            'description'   => Content::html(is_archive() ? get_the_archive_description() : ''),
            'posts'         => $posts,
            'search_query'  => get_search_query(false),
            'primary_menu'  => Content::html((string) wp_nav_menu([
                'theme_location' => 'primary',
                'container'      => '',
                'echo'           => false,
                'fallback_cb'    => false,
                'depth'          => 2,
            ])),
            'footer_menu'   => Content::html((string) wp_nav_menu([
                'theme_location' => 'footer',
                'container'      => '',
                'echo'           => false,
                'fallback_cb'    => false,
                'depth'          => 1,
            ])),
            'pagination'    => Content::html((string) get_the_posts_pagination([
                'prev_text' => __('Zurück', 'sympress-starter'),
                'next_text' => __('Weiter', 'sympress-starter'),
            ])),
            'show_comments' => is_singular() && !post_password_required() && (comments_open() || get_comments_number()),
            'year'          => wp_date('Y'),
        ];

        foreach ($this->composers as $composer) {
            if (!$composer->supports($template)) {
                continue;
            }

            $context = $composer->compose($context, $template);
        }

        /** @var array<string, mixed> $context */
        $context = apply_filters('sympress_starter/context', $context);

        return $context;
    }
}
