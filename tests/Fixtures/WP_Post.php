<?php
// Minimal data object; no WordPress callbacks or rendering are loaded in unit tests.
class WP_Post
{
    public string $post_password = '';
    public string $post_excerpt = '';
    public string $post_content = '';
}
