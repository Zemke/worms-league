const http = require('http');
const fs = require('fs');
const {Client} = require('pg');

const server = http.createServer(async (req, res) => {
  try {
    if (req.url.startsWith("/api")) {
      await onApi(req, res);
    } else if (req.url === '/app.js') {
      res.writeHead(200, {"content-type": "application/javascript"});
      const file = fs.readFileSync('./js/app.js');
      res.end(file);
    } else if (req.url.startsWith('/tpl')) {
      const file = fs.readFileSync('.' + req.url);
      res.end(file);
    } else {
      res.writeHead(200, {"content-type": "text/html"});
      const file = fs.readFileSync('./index.html');
      res.end(file);
    }
  } catch (err) {
    console.error(err);
    res.statusCode = 500;
    res.end('500');
  }
});

async function onApi(req, res) {
  const client = new Client({user: 'postgres', password: 'postgres'});
  try {
    if (req.url === '/api/sign-up' && req.method === 'POST') {
    } else if (req.url === '/api/hello-world' && req.method === 'GET') {
      await client.connect();
      const result = await client.query('SELECT $1::text as message', ['Hello world!']);
      console.log('from db', result.rows[0].message);; // Hello world!
      res.writeHead(200, {"content-type": "application/javascript"});
      res.end(JSON.stringify({response: result.rows[0].message}));
    }
  } finally {
    await client.end();
  }
}

server.listen(7171);

