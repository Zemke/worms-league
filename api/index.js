const http = require('http');
const fs = require('fs');
const {Client} = require('pg');
const bcrypt = require('bcrypt');

const server = http.createServer(async (req, res) => {
  try {
    if (req.url.startsWith("/api")) {
      await onApi(req, res);
    } else if (req.url === '/app.js') {
      res.writeHead(200, {"content-type": "application/javascript"});
      const file = fs.readFileSync('./client/js/app.js');
      res.end(file);
    } else if (req.url.startsWith('/tpl')) {
      const file = fs.readFileSync('./client' + req.url);
      res.end(file);
    } else {
      res.writeHead(200, {"content-type": "text/html"});
      const file = fs.readFileSync('./client/index.html');
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
    await client.connect();
    if (req.url === '/api/sign-up' && req.method === 'POST') {
      const body = await readBody(req);
      console.log('data', body);
      const hash = await bcrypt.hash(body.password, 10);
      const result = await client.query(
            `insert into "user" (username, email, password) values ($1, $2, $3)`,
            [body.username, body.email, hash]);
      console.log('from db', result);
      res.writeHead(200, {"content-type": "application/javascript"});
      res.end(JSON.stringify(result));
    } else if (req.url === '/api/hello-world' && req.method === 'GET') {
      const result = await client.query('SELECT $1::text as message', ['Hello world!']);
      console.log('from db', result.rows[0].message);; // Hello world!
      res.writeHead(200, {"content-type": "application/javascript"});
      res.end(JSON.stringify({response: result.rows[0].message}));
    }
  } finally {
    await client.end();
  }
}

async function readBody(req) {
  return new Promise(resolve => {
    let body = '';
    req.on('data', chunk => body += chunk);
    req.on('end', () => resolve(JSON.parse(body)));
  });
}

module.exports = {listen: port => server.listen(port)};

if (require.main === module) {
const port = process.argv[2] || 7171;
  console.log("port is gonna be " + port);
  server.listen(port);
}

