const http = require('node:http');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const types = { '.html': 'text/html; charset=utf-8', '.css': 'text/css', '.js': 'text/javascript', '.png': 'image/png' };

http.createServer((req, res) => {
  const url = new URL(req.url, 'http://localhost');
  const requested = url.pathname === '/' ? '/docs/design/index.html' : url.pathname;
  const file = path.resolve(root, '.' + requested);
  const allowed = ['/docs/design/', '/resources/css/', '/resources/js/', '/build/'].some(prefix => requested.startsWith(prefix));
  if (!allowed || !file.startsWith(root + path.sep) || !fs.existsSync(file) || !fs.statSync(file).isFile()) {
    res.writeHead(404).end('Not found');
    return;
  }
  res.setHeader('Content-Type', types[path.extname(file)] || 'application/octet-stream');
  fs.createReadStream(file).pipe(res);
}).listen(4178, '127.0.0.1', () => console.log('Screendesign: http://127.0.0.1:4178/docs/design/index.html'));
