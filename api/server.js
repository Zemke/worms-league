const http = require('http');
const fs = require('fs');
const {Client} = require('pg');
const jwt = require('./jwt.js');
const hash = require('./hash.js');
const validate = require('./validate.js');

const server = http.createServer(async (req, res) => {
  if (req.url === '/favicon.ico') {
    return file(res, "image/x-icon", "./client/img/icons/favicon.ico");
  } else if (req.url === "/manifest.json") {
    return file(res, 'application/json',
                "./client/img/icons/manifest.json");
  }
  try {
    if (req.url.startsWith("/api")) {
      return await onApi(req, res);
    } else if (req.url.startsWith('/styles')) {
      return file(res, "text/css", './client' + req.url);
    } else if (req.url.startsWith('/js')) {
      return file(res, "application/javascript", './client' + req.url);
    } else if (req.url.startsWith('/img')) {
      const ext = req.url.match(/\.([^.]+)$/)[1];
      return file(res, `image/${ext}`, './client' + req.url);
    } else if (req.url.startsWith('/tpl')) {
      return file(res, "text/html", './client' + req.url);
    } else {
      return file(res, 'text/html', './client/index.html');
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
      const {username, email, password} = await readBody(req);
      let err;
      if (!validate.req(username)) {
        err = 'validation.required.username';
      } else if (!validate.req(password)) {
        err = 'validation.required.password';
      } else if (!validate.req(email)) {
        err = 'validation.email.required';
      } else if (!validate.email(email)) {
        err = 'validation.email.invalid';
      } else if (!validate.minLen(username, 3)) {
        err = 'validation.username.tooShort';
      } else if (!validate.maxLen(username, 16)) {
        err = 'validation.username.tooLong';
      } else if (!validate.regex(username, /^[a-z0-9-]+$/i)) {
        err = 'validation.username.invalid';
      }
      if (err) return end(res, {err}, 400);
      const result = await client.query(
            'select * from "user" where lower(username) = lower($1)',
            [username]);
      if (result.rows.length > 0) {
        return end(res, {err: 'validation.username.exists'}, 400);
      }
      const hashed = await hash.hash(password);
      await client.query(
            `insert into "user" (username, email, password)
             values ($1, $2, $3)`,
            [username, email, hashed]);
      const token = await jwt.jwtSign({username, email});
      return end(res, {username, email, token});
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
      if (!(await hashed.compare(body.password, rows[0].password))) {
        return end(res, {err: 'wrong credentials'}, 400);
      }
      const token = await jwt.jwtSign({
          username: body.username,
          email: body.email
        });
      return end(res, {token});
    } else if (req.url === '/api/hello-world' && req.method === 'GET') {
      const result = await client.query(
          'SELECT $1::text as message',
          ['Hello world!']);
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

function file(res, contentType, path) {
  try {
    const f = fs.readFileSync(path);
    res.writeHead(200, {"content-type": contentType});
    return res.end(f);
  } catch (err) {
    console.error(err);
    return end(res, undefined, 404);
  }
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

