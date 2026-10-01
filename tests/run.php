<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use SymPress\StarterTheme\View\TemplateResolver;
use SymPress\StarterTheme\View\WordPressExtension;
use SymPress\TwigBundle\Renderer\TwigTemplateRenderer;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Markup;

// Isolated adapters for template tests. Real WordPress is exercised by Playwright.
function __(string $value, string $domain = ''): string { return $value; }
function wp_head(): void { echo '<!-- wp-head-once --><title>Theme test</title>'; }
function wp_footer(): void { echo '<!-- wp-footer-once -->'; }
function wp_body_open(): void { echo '<!-- wp-body-open-once -->'; }
function language_attributes(): void { echo 'lang="de"'; }
function body_class(): void { echo 'class="home"'; }
function comments_template(): void { echo '<section id="comments">Comments</section>'; }

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
    ++$checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$loader = new FilesystemLoader();
$loader->addPath(dirname(__DIR__) . '/resources/views', 'StarterTheme');
$twig = new Environment($loader, ['strict_variables' => true, 'autoescape' => 'html']);
$twig->addExtension(new WordPressExtension());
$renderer = new TwigTemplateRenderer($twig);
$resolver = new TemplateResolver($renderer);
$check($resolver->resolve(['single-book', 'single', 'singular']) === '@StarterTheme/single.html.twig', 'Single type falls back to single.');
$check($resolver->resolve(['front-page', 'page']) === '@StarterTheme/page.html.twig', 'Static homepage falls back to page, not blog.');
$check($resolver->resolve(['unknown']) === '@StarterTheme/index.html.twig', 'Unknown template falls back to index.');

$post = [
    'id' => 1, 'title' => '<script>alert("title")</script>', 'url' => '/article/?a=1&b=2',
    'date' => '1. Oktober 2026', 'date_iso' => '2026-10-01T12:00:00+00:00', 'category' => 'Gestaltung',
    'excerpt' => '<img src=x onerror=alert(1)>', 'image' => '',
    'content' => new Markup('<p>Trusted WordPress block <strong>content</strong>.</p>', 'UTF-8'),
    'pages' => '', 'show_meta' => true,
];
$context = [
    'site' => ['name' => 'SymPress & Journal', 'description' => 'Thoughts', 'url' => '/', 'charset' => 'UTF-8'],
    'title' => 'Title <unsafe>', 'description' => '', 'posts' => [$post, $post],
    'search_query' => '"><script>alert(1)</script>', 'primary_menu' => '', 'footer_menu' => '',
    'pagination' => '', 'show_comments' => false, 'year' => '2026',
];
foreach (['home', 'index', 'archive', 'page', 'single', 'singular', 'search', '404'] as $template) {
    $html = $renderer->render('@StarterTheme/' . $template . '.html.twig', $context);
    $check(!str_contains($html, '<script>'), $template . ': untrusted text escaped.');
    $check(!str_contains($html, '<img src=x'), $template . ': untrusted excerpt escaped.');
    foreach (['wp-head-once', 'wp-footer-once', 'wp-body-open-once'] as $hook) {
        $check(substr_count($html, $hook) === 1, $template . ': native lifecycle hook runs exactly once.');
    }
}
$html = $renderer->render('@StarterTheme/singular.html.twig', [...$context, 'posts' => [$post], 'show_comments' => true]);
$check(str_contains($html, '<strong>content</strong>'), 'Trusted block HTML is preserved.');
$check(str_contains($html, 'id="comments"'), 'Comments rendered when requested.');
$html = $renderer->render('@StarterTheme/search.html.twig', [...$context, 'posts' => []]);
$check(str_contains($html, 'Keine passenden Beiträge'), 'Search empty state exists.');
$html = $renderer->render('@StarterTheme/home.html.twig', [...$context, 'posts' => []]);
$check(str_contains($html, 'Ein Anfang für deine Geschichten.'), 'Empty home has meaningful text.');

$assets = (new SymPress\Assets\Loader\EncoreEntrypointsLoader())
    ->withDirectoryUrl('https://example.test/custom-content/themes/renamed/build/')
    ->load(__DIR__ . '/Fixtures/build/entrypoints.json');
$check(count($assets) >= 3, 'Encore fixture is readable by SymPress Assets without a local build.');
echo "Passed {$checks} assertions.\n";
