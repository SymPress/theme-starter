<?php

declare(strict_types=1);

if (!defined('ABSPATH') || post_password_required()) {
    return;
}
?>
<section class="comments-area" id="comments" aria-labelledby="comments-heading">
    <h2 id="comments-heading"><?php esc_html_e('Im Gespräch', 'sympress-starter'); ?></h2>
    <?php if (have_comments()) : ?>
        <ol class="comment-list">
            <?php wp_list_comments(['style' => 'ol', 'short_ping' => true, 'avatar_size' => 48]); ?>
        </ol>
        <?php the_comments_navigation(); ?>
    <?php endif; ?>
    <?php if (!comments_open() && get_comments_number()) : ?>
        <p><?php esc_html_e('Die Kommentare sind geschlossen.', 'sympress-starter'); ?></p>
    <?php endif; ?>
    <?php comment_form(['title_reply_before' => '<h3 id="reply-title" class="comment-reply-title">', 'title_reply_after' => '</h3>']); ?>
</section>
