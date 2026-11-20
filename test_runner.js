
const path = require('path');
const http = require('http');

// 1. Polyfill fetch to bypass undici WebAssembly requirement
try {
  const nf = require('next/dist/compiled/node-fetch');
  globalThis.fetch = nf.default || nf;
  if (nf.Headers) globalThis.Headers = nf.Headers;
  if (nf.Request) globalThis.Request = nf.Request;
  if (nf.Response) globalThis.Response = nf.Response;
  console.log('[Runner] Polyfilled globalThis.fetch successfully.');
} catch (e) {
  console.error('[Runner] Could not polyfill fetch:', e.message);
}

process.env.NODE_ENV = 'production';
process.chdir(__dirname);

const NextServer = require('next/dist/server/next-server').default;
const requiredServerFiles = require('./.next/required-server-files.json');
const nextConfig = requiredServerFiles.config;
nextConfig.outputFileTracingRoot = __dirname;
process.env.__NEXT_PRIVATE_STANDALONE_CONFIG = JSON.stringify(nextConfig);

const port = 3005;

const nextApp = new NextServer({
  hostname: 'localhost',
  port: port,
  dir: __dirname,
  dev: false,
  customServer: false,
  conf: nextConfig,
});

const handler = nextApp.getRequestHandler();

const server = http.createServer(async (req, res) => {
  try {
    await handler(req, res);
  } catch (err) {
    console.error('Request error:', err);
    if (!res.headersSent) {
      res.statusCode = 500;
      res.end('Internal Server Error');
    }
  }
});

server.listen(port, '127.0.0.1', () => {
  console.log('[Runner] Next.js CRM listening on 127.0.0.1:' + port);
});
