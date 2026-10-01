<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\WordPress;

use Twig\Markup;

final class Context
{
    /** @return array<string, mixed> */
    public function build(): array
    {
        $posts = [];
        while (have_posts()) {
            the_post();
            $protected = post_password_required();
            $categories = get_the_category();
            $content = '';
            if (is_singular()) {
                $content = $protected
                    ? get_the_password_form()
                    : apply_filters('the_content', get_the_content());
            }
            $posts[] = [
                'id' => get_the_ID(),
                'title' => $this->text(get_the_title()),
                'url' => esc_url((string) get_permalink()),
                'date' => get_the_date(),
                'date_iso' => get_the_date(DATE_W3C),
                'category' => $protected ? '' : ($categories[0]->name ?? ''),
                'excerpt' => $protected ? __('Dieser Beitrag ist passwortgeschützt.', 'sympress-starter') : $this->text(get_the_excerpt()),
                'image' => new Markup($protected ? '' : get_the_post_thumbnail(null, 'large', ['class' => 'featured-image']), 'UTF-8'),
                'content' => new Markup((string) $content, 'UTF-8'),
                'pages' => new Markup($protected ? '' : (string) wp_link_pages(['echo' => false]), 'UTF-8'),
                'show_meta' => get_post_type() === 'post',
            ];
        }
        wp_reset_postdata();

        $homeTitle = get_option('page_for_posts') ? get_the_title((int) get_option('page_for_posts')) : get_bloginfo('name');
        $title = match (true) {
            is_404() => __('Diese Seite fehlt.', 'sympress-starter'),
            is_search() => sprintf(__('Suche nach „%s“', 'sympress-starter'), get_search_query(false)),
            is_archive() => $this->text(get_the_archive_title()),
            default => $this->text((string) $homeTitle),
        };

        $context = [
            'site' => [
                'name' => $this->text(get_bloginfo('name')),
                'description' => $this->text(get_bloginfo('description')),
                'url' => esc_url(home_url('/')),
                'charset' => get_bloginfo('charset'),
            ],
            'title' => $title,
            'description' => new Markup(is_archive() ? get_the_archive_description() : '', 'UTF-8'),
            'posts' => $posts,
            'search_query' => get_search_query(false),
            'primary_menu' => new Markup((string) wp_nav_menu([
                'theme_location' => 'primary', 'container' => false, 'echo' => false, 'fallback_cb' => false,
                'depth' => 2,
            ]), 'UTF-8'),
            'footer_menu' => new Markup((string) wp_nav_menu([
                'theme_location' => 'footer', 'container' => false, 'echo' => false, 'fallback_cb' => false, 'depth' => 1,
            ]), 'UTF-8'),
            'pagination' => new Markup((string) get_the_posts_pagination([
                'prev_text' => __('Zurück', 'sympress-starter'),
                'next_text' => __('Weiter', 'sympress-starter'),
            ]), 'UTF-8'),
            'show_comments' => is_singular() && !post_password_required() && (comments_open() || get_comments_number()),
            'year' => wp_date('Y'),
        ];

        /** @var array<string, mixed> $context */
        $context = apply_filters('sympress_starter/context', $context);

        return $context;
    }

    private function text(string $value): string
    {
        return html_entity_decode(wp_strip_all_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
