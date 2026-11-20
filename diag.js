
const nf = require('next/dist/compiled/node-fetch');
globalThis.fetch = nf.default || nf;
globalThis.Headers = nf.Headers;
globalThis.Request = nf.Request;
globalThis.Response = nf.Response;

console.log('1. Polyfilled fetch, Headers, Request, Response');

process.env.NODE_ENV = 'production';
process.chdir(__dirname);
const requiredServerFiles = require('./.next/required-server-files.json');
const nextConfig = requiredServerFiles.config;
nextConfig.outputFileTracingRoot = __dirname;
process.env.__NEXT_PRIVATE_STANDALONE_CONFIG = JSON.stringify(nextConfig);

const NextServer = require('next/dist/server/next-server').default;
console.log('2. Loaded NextServer class');

const nextApp = new NextServer({
  hostname: 'localhost',
  port: 3005,
  dir: __dirname,
  dev: false,
  customServer: false,
  conf: nextConfig,
});
console.log('3. Initialized nextApp instance successfully!');
