const http = require('http');
const fs = require('fs');
const {Client} = require('pg');
const jwt = require('./jwt.js');
const hash = require('./hash.js');

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
    end(res, '500', 500);
  }
});

async function onApi(req, res) {
  const client = new Client({user: 'postgres', password: 'postgres'});
  try {
    await client.connect();
    if (req.url === '/api/sign-up' && req.method === 'POST') {
      const body = await readBody(req);
      console.log('data', body);
      const hash = await hash.hash(body.password);
      const result = await client.query(
            `insert into "user" (username, email, password) values ($1, $2, $3)`,
            [body.username, body.email, hash]);
      console.log('from db', result);
      return end(res, result);
    } else if (req.url === '/api/sign-in' && req.method === 'POST') {
      const body = await readBody(req);
      if (!body?.username || !body?.password) {
        return end(res, {err: 'username and/or password missing'}, 400);
      }
      const {rows} = await client.query(
        'select username, password from "user" where username = $1',
        [body.username]);
      if (rows?.length !== 1) {
        console.info('rows:', rows);
        return end(res, {err: 'wrong credentials'}, 400);
      }
      if (!(await hash.compare(body.password, rows[0].password))) {
        return end(res, {err: 'wrong credentials'}, 400);
      }
      const token = await jwt.jwtSign({hello: 'world'});
      return end(res, {token});
    } else if (req.url === '/api/hello-world' && req.method === 'GET') {
      const result = await client.query('SELECT $1::text as message', ['Hello world!']);
      console.log('from db', result.rows[0].message);; // Hello world!
      return end(res, {response: result.rows[0].message});
    }
  } finally {
    await client.end();
  }
  return end(res, undefined, 404);
}

function end(res, body, status = 200) {
    res.writeHead(status, {"content-type": "application/javascript"});
    const payload = JSON.stringify(body);
    res.end(payload);
    console.info(`Response: ${status}\n${payload}`);
}

async function readBody(req) {
  return new Promise(resolve => {
    let body = '';
    req.on('data', chunk => body += chunk);
    req.on('end', () => {
      try {
        resolve(JSON.parse(body));
      } catch (err) {
        console.error(err);
        resolve(body);
      }
    });
  });
}

module.exports = {listen: port => server.listen(port)};

if (require.main === module) {
const port = process.argv[2] || 7171;
  console.log("port is gonna be " + port);
  server.listen(port);
}

