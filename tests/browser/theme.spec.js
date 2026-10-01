import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

for (const width of [320, 768, 1024, 1440]) {
  test(`home renders without overflow or accessibility violations at ${width}px`, async ({ page }, testInfo) => {
    await page.setViewportSize({ width, height: 1000 });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const failedAssets = [];
    page.on('response', response => {
      if (response.status() >= 400 && /\.(css|js)(\?|$)/.test(response.url())) failedAssets.push(response.url());
    });
    await page.goto('/');
    await expect(page.locator('h1')).toHaveText('SymPress Journal');
    await expect(page.locator('.featured-copy h2')).toHaveText('Weniger Ablenkung. Mehr Inhalt.');
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    expect(await page.locator('.type-cover').evaluate(el => getComputedStyle(el).backgroundColor)).toBe('rgb(56, 88, 233)');
    await expect(page.locator('head style[data-id="sympress-starter-app"]')).toHaveCount(1);
    await expect(page.locator('link[rel="stylesheet"][href*="sympress-starter-app"]')).toHaveCount(0);
    expect(await page.evaluate(() => performance.getEntriesByType('resource').some(
      resource => /sympress-starter-app.*\.css/.test(resource.name),
    ))).toBe(false);
    expect(errors).toEqual([]);
    expect(failedAssets).toEqual([]);
    const accessibility = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
    expect(accessibility.violations).toEqual([]);
    if (width === 1440 || width === 320) {
      await page.screenshot({ path: testInfo.outputPath(`theme-${width}.png`), fullPage: true });
    }
  });
}

test('public pages have one relevant meta description and protected pages do not leak one', async ({ page }) => {
  const descriptions = [];
  for (const route of ['/', '/gedanken-3/', '/ueber/']) {
    await page.goto(route);
    const meta = page.locator('head meta[name="description"]');
    await expect(meta).toHaveCount(1);
    const description = await meta.getAttribute('content');
    expect(description.length).toBeGreaterThan(15);
    expect(description).not.toMatch(/<[^>]+>|DO_NOT_LEAK_PROTECTED/);
    descriptions.push(description);
  }
  expect(new Set(descriptions).size).toBe(3);
  await page.goto('/');
  await expect(page.locator('meta[name="description"]')).toHaveAttribute('content', 'Notizen, Ideen und Geschichten, die bleiben.');
  for (const route of ['/geschuetzt/', '/?s=Notizbuch', '/there-is-no-such-page/']) {
    await page.goto(route);
    await expect(page.locator('meta[name="description"]')).toHaveCount(0);
  }
});

test('mobile keyboard menu, Escape and no-JavaScript fallback', async ({ page, browser }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/');
  const menu = page.getByRole('button', { name: 'Menü' });
  const nav = page.locator('#primary-navigation');
  await expect(nav).toBeHidden();
  await page.keyboard.press('Tab');
  await expect(page.getByRole('link', { name: 'Zum Inhalt' })).toBeFocused();
  await menu.focus();
  await page.keyboard.press('Enter');
  await expect(nav).toBeVisible();
  await page.keyboard.press('Tab');
  await expect(nav.getByRole('link', { name: 'Journal', exact: true })).toBeFocused();
  await page.keyboard.press('Escape');
  await expect(nav).toBeHidden();
  await expect(menu).toBeFocused();
  const context = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 320, height: 844 } });
  const plain = await context.newPage();
  await plain.goto('/');
  await expect(plain.locator('#primary-navigation')).toBeVisible();
  await expect(plain.getByRole('button', { name: 'Menü' })).toBeHidden();
  await expect(plain.locator('.type-cover')).toHaveCSS('background-color', 'rgb(56, 88, 233)');
  await context.close();
});

test('search submits, escapes input and handles no results', async ({ page }) => {
  await page.goto('/?s=');
  await page.getByRole('searchbox').fill('Notizbuch');
  await page.getByRole('button', { name: 'Suchen', exact: true }).click();
  await expect(page.locator('.post-summary h2')).toHaveText('Das offene Notizbuch.');
  await page.getByRole('searchbox').fill('"><script>alert(1)</script>');
  await page.getByRole('button', { name: 'Suchen', exact: true }).click();
  await expect(page.getByText('Keine passenden Beiträge gefunden.')).toBeVisible();
  expect(await page.locator('main script').count()).toBe(0);
  expect((await new AxeBuilder({ page }).analyze()).violations).toEqual([]);
});

test('single content, comments, page, archive and pagination work', async ({ page }) => {
  await page.goto('/gedanken-3/');
  await expect(page.locator('h1')).toHaveText('Weniger Ablenkung. Mehr Inhalt.');
  await expect(page.locator('.entry-content h2')).toHaveText('Raum für eigene Inhalte');
  await expect(page.locator('#comment')).toBeVisible();
  expect((await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze()).violations).toEqual([]);
  await page.goto('/ueber/');
  await expect(page.locator('h1')).toHaveText('Über dieses Journal');
  await page.goto('/?cat=1');
  await expect(page.locator('.post-grid article')).toHaveCount(3);
  await page.goto('/');
  await page.locator('.pagination').getByRole('link', { name: '2', exact: true }).click();
  await expect(page.locator('.featured-copy h2')).toHaveText('Ein genauerer Blick.');
});

test('password protected posts never leak content or excerpts', async ({ page }) => {
  await page.goto('/geschuetzt/');
  await expect(page.locator('input[type="password"]')).toBeVisible();
  expect(await page.content()).not.toContain('DO_NOT_LEAK_PROTECTED');
  await expect(page.locator('#comments')).toHaveCount(0);
  await page.goto('/?s=Geschützter');
  expect(await page.content()).not.toContain('DO_NOT_LEAK_PROTECTED');
});

test('missing page returns a real 404 and usable search', async ({ page }) => {
  const response = await page.goto('/there-is-no-such-page/');
  expect(response.status()).toBe(404);
  await expect(page.locator('h1')).toHaveText('Diese Seite fehlt.');
  await expect(page.getByRole('searchbox')).toBeVisible();
  expect((await new AxeBuilder({ page }).analyze()).violations).toEqual([]);
});

test('block editor loads scoped theme styles inside its canvas', async ({ page }, testInfo) => {
  await page.goto('/wp/wp-login.php');
  await page.locator('#user_login').fill('theme-test');
  await page.locator('#user_pass').fill('local-fixture-only-no-reuse');
  await page.locator('#wp-submit').click();
  await page.goto('/wp/wp-admin/post-new.php');
  const editor = page.frameLocator('iframe[name="editor-canvas"]').locator('.editor-styles-wrapper');
  await expect(editor).toBeVisible();
  await expect(editor).toHaveCSS('background-color', 'rgb(246, 247, 247)');
  await expect(editor).toHaveCSS('color', 'rgb(16, 21, 23)');
  expect(await page.locator('link[href*="sympress-starter-app"]').count()).toBe(0);
  await page.evaluate(() => {
    if (wp.data.select('core/edit-post').isFeatureActive('welcomeGuide')) {
      wp.data.dispatch('core/edit-post').toggleFeature('welcomeGuide');
    }
  });
  await expect(page.getByRole('dialog')).toHaveCount(0);
  await page.screenshot({ path: testInfo.outputPath('editor.png'), fullPage: true });
});
