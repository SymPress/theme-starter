import webpack from 'webpack';
import { chromium } from '@playwright/test';
import { createServer } from 'node:http';
import { readFile, readdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import configPromise from '../webpack.config.js';

const config = await configPromise;
const directory = fileURLToPath(new URL('../var/chunk-check/', import.meta.url));
config.entry = { probe: fileURLToPath(new URL('../tests/Fixtures/chunks/entry.js', import.meta.url)) };
config.output.path = directory;
// Encore's manifest plugins capture the normal build directory at construction.
// This isolated runtime probe needs only webpack's actual entry/chunk output.
config.plugins = config.plugins.filter(plugin => !['EntryPointsPlugin', 'WebpackManifestPlugin', 'AssetOutputDisplayPlugin'].includes(plugin.constructor.name));
await new Promise((resolve, reject) => {
  const compiler = webpack(config);
  compiler.run((error, stats) => {
    compiler.close(closeError => {
      if (error || closeError || stats.hasErrors()) reject(error || closeError || new Error(stats.toString()));
      else resolve();
    });
  });
});
const entry = (await readdir(directory)).find(name => /^probe\..*\.js$/.test(name));
if (!entry) throw new Error('Probe entry missing.');
const failures = [];
const server = createServer(async (request, response) => {
  const url = new URL(request.url, 'http://localhost');
  if (url.pathname === '/nested/page/') {
    response.setHeader('Content-Type', 'text/html');
    response.end(`<html><body><script src="/theme/build/${entry}"></script></body></html>`);
    return;
  }
  if (url.pathname.startsWith('/theme/build/') && !url.pathname.slice(13).includes('/')) {
    try {
      response.setHeader('Content-Type', 'text/javascript');
      response.end(await readFile(directory + url.pathname.slice(13)));
      return;
    } catch { /* Report the missing request below. */ }
  }
  if (url.pathname !== '/favicon.ico') failures.push(url.pathname);
  response.writeHead(404).end();
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
let browser;
try {
  browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  page.on('pageerror', error => failures.push(error.message));
  await page.goto(`http://127.0.0.1:${server.address().port}/nested/page/`);
  await page.waitForFunction(() => document.body.dataset.chunkResult === 'loaded-from-theme-assets');
  if (failures.length) throw new Error(failures.join('\n'));
  console.log('PASS: a real dynamic import loads from /theme/build/ on /nested/page/.');
} finally {
  await browser?.close();
  await new Promise(resolve => server.close(resolve));
}
