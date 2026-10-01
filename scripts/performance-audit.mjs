import { chromium } from '@playwright/test';
import { mkdir, writeFile, readFile, readdir } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import http from 'node:http';
import { performance } from 'node:perf_hooks';
import { gzipSync, brotliCompressSync, gunzipSync, brotliDecompressSync, inflateSync } from 'node:zlib';
import { execFileSync } from 'node:child_process';

// Local read-only benchmark. Does not seed content, switch themes or tune caches.
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const base = process.env.PERF_URL || 'http://127.0.0.1:18943';
if (!['127.0.0.1', 'localhost'].includes(new URL(base).hostname)) {
  throw new Error('This runner is restricted to a local test site.');
}
const output = path.resolve(root, process.env.PERF_OUTPUT || 'docs/performance/2026-10-01');
await mkdir(output, { recursive: true });
const routes = { home: '/', article: '/gedanken-3/', page: '/ueber/', search: '/?s=Notizbuch' };
const round = n => Math.round(n * 100) / 100;
const summary = values => {
  const sorted = [...values].sort((a, b) => a - b);
  const n = sorted.length;
  return {
    n, min: round(sorted[0]), median: round(n % 2 ? sorted[(n - 1) / 2] : (sorted[n / 2 - 1] + sorted[n / 2]) / 2),
    p95: round(sorted[Math.ceil(n * .95) - 1]), max: round(sorted[n - 1]),
  };
};

function request(url, collectBody = false) {
  return new Promise((resolve, reject) => {
    const start = performance.now();
    const req = http.get(url, { agent: false, headers: { 'Accept-Encoding': 'gzip, br', 'User-Agent': 'SymPress-local-performance-audit/1.0' } }, res => {
      const ttfbMs = performance.now() - start;
      const chunks = [];
      let bytes = 0;
      res.on('data', chunk => { bytes += chunk.length; if (collectBody) chunks.push(chunk); });
      res.on('end', () => {
        const totalMs = round(performance.now() - start);
        try {
          let body = Buffer.concat(chunks);
          if (collectBody) {
            const decode = { gzip: gunzipSync, br: brotliDecompressSync, deflate: inflateSync }[res.headers['content-encoding']];
            if (decode) body = decode(body);
          }
          resolve({ status: res.statusCode, ttfbMs: round(ttfbMs), totalMs, bytes, headers: res.headers, ...(collectBody ? { body: body.toString(), decodedBytes: body.length } : {}) });
        } catch (error) { reject(error); }
      });
      res.on('error', reject);
    });
    req.setTimeout(15000, () => req.destroy(new Error('Request timed out')));
    req.on('error', reject);
  });
}

const report = { timestamp: new Date().toISOString(), base, environment: { node: process.version, platform: process.platform, measurement: 'Local HTTP; warmed server; no restarts or cache purges; new browser context per navigation' }, http: {}, assets: [], browser: [], lighthouse: [] };
for (const [name, route] of Object.entries(routes)) {
  const first = await request(base + route);
  const samples = [];
  for (let i = 0; i < 20; i++) samples.push(await request(base + route));
  report.http[name] = { route, firstObserved: first, ttfbMs: summary(samples.map(s => s.ttfbMs)), totalMs: summary(samples.map(s => s.totalMs)), samples };
  console.log(`HTTP ${name}: median TTFB ${report.http[name].ttfbMs.median} ms; p95 ${report.http[name].ttfbMs.p95} ms`);
}

const startBurst = performance.now();
const burst = [];
await Promise.all(Array.from({ length: 4 }, async () => {
  for (let i = 0; i < 5; i++) burst.push(await request(base + '/'));
}));
report.boundedConcurrency = { concurrency: 4, requests: 20, durationMs: round(performance.now() - startBurst), ttfbMs: summary(burst.map(s => s.ttfbMs)), statuses: burst.map(s => s.status), note: 'PHP CLI development server, single worker; this is queue behavior, not production capacity.' };

for (const file of await readdir(root + '/build')) {
  if (!/\.(css|js)$/.test(file)) continue;
  const bytes = await readFile(root + '/build/' + file);
  const res = await request(base + '/wp-content/themes/sympress-starter/build/' + file);
  report.assets.push({ file, bytes: bytes.length, theoreticalGzipBytes: gzipSync(bytes).length, theoreticalBrotliBytes: brotliCompressSync(bytes).length, status: res.status, headers: res.headers });
}
const document = await request(base + '/', true);
report.document = { bytes: document.bytes, decodedBytes: document.decodedBytes, theoreticalGzipBytes: gzipSync(document.body).length, headers: document.headers, inlineStyles: [...document.body.matchAll(/<style\b([^>]*)>([\s\S]*?)<\/style>/g)].map(m => ({ attributes: m[1], bytes: Buffer.byteLength(m[2]) })) };

const browser = await chromium.launch();
report.environment.chromium = browser.version();
try {
  for (const [device, viewport] of Object.entries({ desktop: { width: 1440, height: 1000 }, mobile: { width: 390, height: 844 } })) {
    for (const name of ['home', 'article', 'search']) {
      const samples = [];
      for (let run = 1; run <= 3; run++) {
        const context = await browser.newContext({ viewport, isMobile: device === 'mobile', deviceScaleFactor: 1, hasTouch: device === 'mobile' });
        const page = await context.newPage();
        const failures = [];
        page.on('pageerror', e => failures.push(e.message));
        page.on('requestfailed', r => failures.push(r.url() + ': ' + r.failure()?.errorText));
        await page.addInitScript(() => {
          window.audit = { lcp: [], shifts: [], longTasks: [], events: [] };
          new PerformanceObserver(list => {
            for (const e of list.getEntries()) window.audit.lcp.push({ startTime: e.startTime, size: e.size, element: e.element?.tagName, text: e.element?.textContent?.trim().slice(0, 90) });
          }).observe({ type: 'largest-contentful-paint', buffered: true });
          new PerformanceObserver(list => {
            for (const e of list.getEntries()) if (!e.hadRecentInput) window.audit.shifts.push({ time: e.startTime, value: e.value });
          }).observe({ type: 'layout-shift', buffered: true });
          new PerformanceObserver(list => {
            for (const e of list.getEntries()) window.audit.longTasks.push({ time: e.startTime, duration: e.duration });
          }).observe({ type: 'longtask', buffered: true });
          new PerformanceObserver(list => {
            for (const e of list.getEntries()) if (e.interactionId) window.audit.events.push({ name: e.name, duration: e.duration, interactionId: e.interactionId });
          }).observe({ type: 'event', buffered: true, durationThreshold: 16 });
        });
        const response = await page.goto(base + routes[name], { waitUntil: 'networkidle' });
        await page.waitForTimeout(400);
        if (device === 'mobile' && name === 'home') {
          await page.getByRole('button', { name: 'Menü', exact: true }).click();
          await page.keyboard.press('Escape');
          await page.waitForTimeout(200);
        }
        const sample = await page.evaluate(() => {
          const navigation = performance.getEntriesByType('navigation')[0];
          let cls = 0, session = 0, first = 0, last = 0;
          for (const s of window.audit.shifts) {
            if (session && s.time - last < 1000 && s.time - first < 5000) session += s.value;
            else { session = s.value; first = s.time; }
            last = s.time;
            cls = Math.max(cls, session);
          }
          return {
            ttfbMs: navigation.responseStart - navigation.requestStart,
            navigationMs: navigation.duration,
            domContentLoadedMs: navigation.domContentLoadedEventEnd,
            fcpMs: performance.getEntriesByName('first-contentful-paint')[0]?.startTime,
            lcp: window.audit.lcp.at(-1), cls, longTasks: window.audit.longTasks,
            observedInteractionEvents: window.audit.events,
            resourceTiming: performance.getEntriesByType('resource').map(e => ({ url: e.name, type: e.initiatorType, transferBytes: e.transferSize, encodedBytes: e.encodedBodySize, durationMs: e.duration })),
            navigationBytes: navigation.transferSize,
            domElements: document.querySelectorAll('*').length,
          };
        });
        samples.push({ run, status: response.status(), ...sample, failures });
        await context.close();
      }
      const item = { device, page: name, throttling: 'none; mobile viewport only (Lighthouse below simulates network/CPU)', samples, ttfbMs: summary(samples.map(s => s.ttfbMs)), fcpMs: summary(samples.map(s => s.fcpMs)), lcpMs: summary(samples.map(s => s.lcp?.startTime || 0)), cls: samples.map(s => s.cls) };
      report.browser.push(item);
      console.log(`Browser ${device}/${name}: median LCP ${item.lcpMs.median} ms; CLS ${Math.max(...item.cls)}`);
    }
  }
} finally {
  await browser.close();
}

await writeFile(output + '/measurements.json', JSON.stringify(report, null, 2));
if (!process.env.SKIP_LIGHTHOUSE) {
  for (const device of ['mobile', 'desktop']) {
    for (let run = 1; run <= 3; run++) {
      const file = `${device}-${run}`;
      const args = ['--yes', 'lighthouse@13.5.0', base + '/', '--chrome-flags=--headless --no-sandbox --disable-dev-shm-usage', '--only-categories=performance,accessibility,best-practices,seo', '--output=json', '--output=html', '--output-path=' + output + '/' + file, '--quiet'];
      if (device === 'desktop') args.push('--preset=desktop');
      execFileSync('npx', args, { cwd: root, env: { ...process.env, CHROME_PATH: process.env.CHROME_PATH || chromium.executablePath() }, timeout: 120000, stdio: ['ignore', 'ignore', 'pipe'] });
      const lhr = JSON.parse(await readFile(output + '/' + file + '.report.json', 'utf8'));
      const item = { device, run, report: file + '.report.html', lighthouseVersion: lhr.lighthouseVersion, scores: Object.fromEntries(Object.entries(lhr.categories).map(([key, value]) => [key, value.score * 100])), metrics: Object.fromEntries(['first-contentful-paint','largest-contentful-paint','total-blocking-time','cumulative-layout-shift','speed-index','server-response-time','total-byte-weight'].map(key => [key, { value: lhr.audits[key]?.numericValue, display: lhr.audits[key]?.displayValue }])), warnings: lhr.runWarnings, runtimeError: lhr.runtimeError, settings: lhr.configSettings, failures: Object.values(lhr.audits).filter(a => a.score !== null && a.score < 1).map(a => ({ id: a.id, title: a.title, score: a.score, display: a.displayValue, savings: a.metricSavings, details: a.details })) };
      report.lighthouse.push(item);
      await writeFile(output + '/measurements.json', JSON.stringify(report, null, 2));
      console.log(`Lighthouse ${device} ${run}/3: performance ${item.scores.performance}; LCP ${item.metrics['largest-contentful-paint'].display}`);
    }
  }
}
console.log(`Saved raw measurements and Lighthouse reports in ${output}`);
