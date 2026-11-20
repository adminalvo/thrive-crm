const path = require('path');
const http = require('http');
const NextServer = require('next/dist/server/next-server').default;

process.env.NODE_ENV = 'production';
process.chdir(__dirname);

// Load and sanitize Next.js config
const requiredServerFiles = require('./.next/required-server-files.json');
const nextConfig = requiredServerFiles.config;
nextConfig.outputFileTracingRoot = __dirname;
process.env.__NEXT_PRIVATE_STANDALONE_CONFIG = JSON.stringify(nextConfig);

const port = process.env.PORT || 3000;

const nextApp = new NextServer({
  hostname: 'localhost',
  port: typeof port === 'number' ? port : 3000,
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

server.listen(port, () => {
  console.log('CRM Server successfully listening on:', port);
});
