<?php
/**
 * Title: Editorial introduction
 * Slug: sympress-starter/editorial-intro
 * Categories: text
 * Description: A spacious introduction using the theme's editorial typography.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!-- wp:group {"layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"3rem","bottom":"3rem"}}}} -->
<div class="wp-block-group" style="padding-top:3rem;padding-bottom:3rem">
<!-- wp:heading {"fontSize":"x-large"} -->
<h2 class="wp-block-heading has-x-large-font-size"><?php esc_html_e('Ein Anfang. Deine Handschrift.', 'sympress-starter'); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php esc_html_e('Hier beginnt deine Geschichte. Ersetze diesen Text durch deine eigene Einführung.', 'sympress-starter'); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
