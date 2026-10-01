<?php

declare(strict_types=1);

if (wp_get_environment_type() !== 'local' || DB_NAME !== 'theme_test') {
    throw new RuntimeException('This fixture is restricted to the disposable theme_test database.');
}

update_option('blogname', 'SymPress Journal');
update_option('blogdescription', 'Notizen, Ideen und Geschichten, die bleiben.');
update_option('posts_per_page', 3);
update_option('permalink_structure', '/%postname%/');
update_option('thread_comments', 1);
update_option('show_on_front', 'posts');
switch_theme('sympress-starter');

// Deterministic fixture: only this database is reset.
foreach (get_posts(['numberposts' => -1, 'post_type' => ['post', 'page'], 'post_status' => 'any']) as $post) {
    wp_delete_post($post->ID, true);
}
$titles = ['Ein genauerer Blick.', 'Das offene Notizbuch.', 'Platz für einen anderen Blick.', 'Weniger Ablenkung. Mehr Inhalt.'];
foreach ($titles as $index => $title) {
    wp_insert_post([
        'post_title' => $title,
        'post_name' => 'gedanken-' . $index,
        'post_status' => 'publish',
        'post_date' => '2026-09-' . (24 + $index) . ' 12:00:00',
        'post_excerpt' => 'Eine gute Website gibt Gedanken Raum. Über klare Typografie, bewusste Entscheidungen und den Mut, etwas wegzulassen.',
        'post_content' => '<!-- wp:paragraph --><p>Eine gute Website gibt Gedanken Raum. Ein funktionierendes Fundament macht den Anfang leichter.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Raum für eigene Inhalte</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Dies sind ausdrücklich Beispielinhalte für den lokalen Theme-Test. <a href="/?s=Gedanken">Weitere Gedanken finden</a>.</p><!-- /wp:paragraph --><!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li>Klare Typografie</li><!-- /wp:list-item --><!-- wp:list-item --><li>Gut lesbare Inhalte</li><!-- /wp:list-item --></ul><!-- /wp:list -->',
        'comment_status' => 'open',
    ]);
}
$page = wp_insert_post(['post_type' => 'page', 'post_title' => 'Über dieses Journal', 'post_name' => 'ueber', 'post_status' => 'publish', 'post_content' => '<p>Ein Anfang. Deine Handschrift. Diese Seite gehört zur lokalen Testinstallation.</p>']);
wp_insert_post(['post_title' => 'Geschützter Beitrag', 'post_name' => 'geschuetzt', 'post_status' => 'publish', 'post_password' => 'fixture-only', 'post_content' => 'DO_NOT_LEAK_PROTECTED_CONTENT', 'post_excerpt' => 'DO_NOT_LEAK_PROTECTED_EXCERPT', 'post_date' => '2026-09-01 12:00:00']);
$menu = wp_get_nav_menu_object('Test navigation');
$menuId = $menu ? $menu->term_id : wp_create_nav_menu('Test navigation');
foreach (wp_get_nav_menu_items($menuId) ?: [] as $item) {
    wp_delete_post($item->ID, true);
}
wp_update_nav_menu_item($menuId, 0, ['menu-item-title' => 'Journal', 'menu-item-url' => home_url('/'), 'menu-item-type' => 'custom', 'menu-item-status' => 'publish']);
wp_update_nav_menu_item($menuId, 0, ['menu-item-object-id' => $page, 'menu-item-object' => 'page', 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish']);
wp_update_nav_menu_item($menuId, 0, ['menu-item-title' => 'Suche', 'menu-item-url' => home_url('/?s='), 'menu-item-type' => 'custom', 'menu-item-status' => 'publish']);
set_theme_mod('nav_menu_locations', ['primary' => $menuId]);
flush_rewrite_rules();
echo "Theme fixture created.\n";
