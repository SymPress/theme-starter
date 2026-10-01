import assert from 'node:assert/strict';
import http from 'node:http';
import { readFile } from 'node:fs/promises';
import { gunzipSync } from 'node:zlib';

// Exercise the disposable fixture's delivery rules, including decoded integrity.
const base = process.env.THEME_TEST_URL || 'http://127.0.0.1:18943';
assert.ok(['127.0.0.1', 'localhost'].includes(new URL(base).hostname));
const entries = JSON.parse(await readFile(new URL('../../build/entrypoints.json', import.meta.url)));
const app = entries.entrypoints['sympress-starter-app'];

function request(path, encoding = 'gzip', method = 'GET') {
  return new Promise((resolve, reject) => {
    const req = http.get(new URL(path, base), { method, headers: { 'Accept-Encoding': encoding } }, res => {
      const chunks = [];
      res.on('data', chunk => chunks.push(chunk));
      res.on('end', () => resolve({ status: res.statusCode, headers: res.headers, body: Buffer.concat(chunks) }));
      res.on('error', reject);
    });
    req.setTimeout(15000, () => req.destroy(new Error('HTTP check timed out')));
    req.on('error', reject);
  });
}

for (const asset of [...app.css, ...app.js]) {
  const name = asset.split('/').at(-1);
  const route = '/wp-content/themes/sympress-starter/build/' + name;
  const source = await readFile(new URL('../../build/' + name, import.meta.url));
  const zipped = await request(route);
  assert.equal(zipped.status, 200);
  assert.equal(zipped.headers['content-encoding'], 'gzip');
  assert.match(zipped.headers.vary, /Accept-Encoding/i);
  assert.match(zipped.headers['cache-control'], /max-age=31536000, immutable/);
  assert.deepEqual(gunzipSync(zipped.body), source);
  assert.ok(zipped.body.length < source.length);
  const plain = await request(route, 'identity');
  assert.equal(plain.headers['content-encoding'], undefined);
  assert.deepEqual(plain.body, source);
  const head = await request(route, 'identity', 'HEAD');
  assert.equal(head.status, 200);
  assert.equal(head.body.length, 0);
  console.log(`${name}: compressed ${zipped.body.length}/${source.length} bytes; immutable; identity and HEAD passed`);
}

for (const route of ['/', '/?s=Notizbuch']) {
  const response = await request(route);
  assert.equal(response.status, 200);
  assert.equal(response.headers['content-encoding'], 'gzip');
  assert.match(gunzipSync(response.body).toString(), /<html\b/i);
  assert.doesNotMatch(response.headers['cache-control'] || '', /public|immutable|max-age=31536000/);
}
const manifest = await request('/wp-content/themes/sympress-starter/build/entrypoints.json', 'identity');
assert.equal(manifest.status, 200);
assert.doesNotMatch(manifest.headers['cache-control'] || '', /immutable/);
console.log('HTML compression passed; HTML and unversioned manifest are not cached as immutable.');
