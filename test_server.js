const http = require('http');
const port = 3005;

const server = http.createServer((req, res) => {
  res.writeHead(200, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify({
    status: 'ok',
    message: 'Hello from Node.js on cPanel!',
    node_version: process.version,
    uptime: process.uptime(),
    time: new Date().toISOString()
  }));
});

server.listen(port, '127.0.0.1', () => {
  console.log(`Test server running at http://127.0.0.1:${port}/`);
});