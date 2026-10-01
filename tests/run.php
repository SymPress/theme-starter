<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use SymPress\TwigBundle\WordPress\Runtime;
use SymPress\TwigBundle\WordPress\ThemeConfiguration;
use Twig\Environment;
use Twig\Extension\AttributeExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Markup;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

// Isolated template contracts; real WordPress behavior is covered by the disposable fixture.
function __(string $value, string $domain = ''): string { return $value; }
function wp_head(): void { echo '<!-- wp-head-once --><title>Theme test</title>'; }
function wp_footer(): void { echo '<!-- wp-footer-once -->'; }
function wp_body_open(): void { echo '<!-- wp-body-open-once -->'; }
function get_language_attributes(): string { return 'lang="de"'; }
function body_class(string|array $extra = ''): void { echo 'class="home"'; }
function comments_template(): void { echo '<section id="comments">Comments</section>'; }
function post_password_required(): bool { return false; }
function is_singular(): bool { return true; }
function comments_open(): bool { return false; }
function get_nav_menu_locations(): array { return []; }
function wp_timezone(): DateTimeZone { return new DateTimeZone('Europe/Berlin'); }
function wp_date(string $format, int $time, DateTimeZone $zone): string { return (new DateTimeImmutable('@' . $time))->setTimezone($zone)->format($format); }
function wp_link_pages(array $args): string { return ''; }

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
    ++$checks;
    if (!$condition) { throw new RuntimeException($message); }
};
$loader = new FilesystemLoader();
$loader->addPath(dirname(__DIR__) . '/resources/views', 'theme');
$bundle = dirname((new ReflectionClass(ThemeConfiguration::class))->getFileName(), 3);
$loader->addPath($bundle . '/Resources/views', 'wordpress');
$twig = new Environment($loader, ['strict_variables' => true, 'autoescape' => 'html']);
foreach ([Runtime\TemplateRuntime::class, Runtime\TranslationRuntime::class, Runtime\EscapingRuntime::class, Runtime\QueryRuntime::class] as $runtime) {
    $twig->addExtension(new AttributeExtension($runtime));
}
$twig->addRuntimeLoader(new FactoryRuntimeLoader([
    Runtime\TemplateRuntime::class => static fn () => new Runtime\TemplateRuntime(),
    Runtime\TranslationRuntime::class => static fn () => new Runtime\TranslationRuntime(new ThemeConfiguration()),
]));
$post = new class {
    public int $id = 1;
    public string $title = '<script>alert("title")</script>';
    public string $url = '/article/?a=1&b=2';
    public string $date = '1. Oktober 2026';
    public string $date_iso = '2026-10-01T12:00:00+00:00';
    public array $categories = [];
    public string $type = 'post';
    public string $excerpt = '<img src=x onerror=alert(1)>';
    public function thumbnail(string $size, array $attributes): string { return ''; }
    public function content(): Markup { return new Markup('<p>Trusted WordPress block <strong>content</strong>.</p>', 'UTF-8'); }
};
$context = [
    'site' => ['name' => 'SymPress & Journal', 'description' => 'Thoughts', 'url' => '/', 'charset' => 'UTF-8'],
    'title' => 'Title <unsafe>', 'description' => '', 'posts' => [$post, $post],
    'search_query' => '"><script>alert(1)</script>', 'show_comments' => false,
];
foreach (['home', 'index', 'singular', 'search', '404', 'custom/landing'] as $template) {
    $html = $twig->render('@theme/' . $template . '.html.twig', $context);
    $check(!str_contains($html, '<script>'), $template . ': untrusted text escaped.');
    $check(!str_contains($html, '<img src=x'), $template . ': untrusted excerpt escaped.');
    foreach (['wp-head-once', 'wp-footer-once', 'wp-body-open-once'] as $hook) {
        $check(substr_count($html, $hook) === 1, $template . ': lifecycle hook runs exactly once.');
    }
}
$html = $twig->render('@theme/singular.html.twig', [...$context, 'posts' => [$post], 'show_comments' => true]);
$check(str_contains($html, '<strong>content</strong>'), 'Trusted block HTML is preserved.');
$check(str_contains($html, 'id="comments"'), 'Comments rendered when requested.');
$html = $twig->render('@theme/search.html.twig', [...$context, 'posts' => []]);
$check(str_contains($html, 'Keine passenden Beiträge'), 'Search empty state exists.');
$html = $twig->render('@theme/home.html.twig', [...$context, 'posts' => []]);
$check(str_contains($html, 'Ein Anfang für deine Geschichten.'), 'Empty home has meaningful text.');
$assets = (new SymPress\Assets\Loader\EncoreEntrypointsLoader())
    ->withDirectoryUrl('https://example.test/custom-content/themes/renamed/build/')
    ->fromFile(__DIR__ . '/Fixtures/build/entrypoints.json');
$check(count($assets) >= 3, 'Validated Encore fixture is readable.');
// Exercise the native fallback without booting a second container.
function esc_html__(string $value, string $domain = ''): string { return htmlspecialchars($value, ENT_QUOTES); }
function wp_die(string $message, string $title, array $args): never {
    if ($args['response'] !== 503 || !str_contains($message, 'template_include') || !str_contains($message, 'kernel')) {
        throw new RuntimeException('The missing Twig bridge must report an actionable HTTP 503.');
    }
    throw new LogicException('expected-503');
}
define('ABSPATH', __DIR__);
try {
    require dirname(__DIR__) . '/index.php';
    throw new RuntimeException('Fallback must stop the request.');
} catch (LogicException $exception) {
    $check($exception->getMessage() === 'expected-503', 'Missing bridge produces an actionable 503.');
}
echo "Passed {$checks} assertions.\n";
