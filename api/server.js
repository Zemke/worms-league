const http = require('http');
const fs = require('fs');
const { Pool, Client } = require('pg');
const jwt = require('./jwt.js');
const hash = require('./hash.js');
const validate = require('./validate.js');
const waaas = require('./waaas.js');
const formidable = require('formidable');

const pool = new Pool({ user: 'postgres', password: 'postgres' }) // TODO env vars

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
  if (req.url === '/api/sign-up' && req.method === 'POST') {
    const {username, email, password, spamcheck} = await readBody(req);
    const spamcheckValids = ['testing']; // TODO Externalize configuration.
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
    } else if (!spamcheckValids?.includes(spamcheck)) {
      err = 'validation.spamcheck';
    }
    if (err) return end(res, {err}, 400);
    const result = await pool.query(
          'select * from "user" where lower(username) = lower($1)',
          [username]);
    if (result.rows.length > 0) {
      return end(res, {err: 'validation.username.exists'}, 400);
    }
    const hashed = await hash.hash(password);
    await pool.query(
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
    const {rows} = await pool.query(
      'select username, password from "user" where username = $1',
      [body.username]);
    if (rows?.length !== 1) {
      console.info('rows:', rows);
      return end(res, {err: 'wrong credentials'}, 400);
    }
    if (!(await hash.compare(body.password, rows[0].password))) {
      return end(res, {err: 'wrong credentials'}, 400);
    }
    const token = await jwt.jwtSign({
        username: body.username,
        email: body.email
      });
    return end(res, {token});
  } else if (req.url === '/api/game' && req.method === 'POST') {
    const form = await new Promise((resolve, reject) => {
      formidable().parse(req, (err, fields, files) => {
        err ? reject(err) : resolve({fields, files});
      });
    });

    //{
    //  fields: {},
    //  files: {
    //    replay0: File {
    //      _events: [Object: null prototype] {},
    //      _eventsCount: 0,
    //      _maxListeners: undefined,
    //      size: 42288,
    //      path: '/var/folders/d8/yhw8dhsn0njgq7txxng0b_380000gn/T/upload_6f7a35029efd00bc984f6e0fc06260d0',
    //      name: '2021-06-17 15.38.17 [Online Round 3] TdCxSenator, @Albus.WAgame',
    //      type: 'application/octet-stream',
    //      hash: null,
    //      lastModifiedDate: 2021-06-20T08:52:47.714Z,
    //      _writeStream: [WriteStream],
    //      [Symbol(kCapture)]: false
    //    },
    //    ...
    //  }
    //}
    console.log('form', form);

    for (const file in form.files) {
      if (!file.name.toLowerCase().endsWith('.wagame')) {
        return end(res, {[file]: 'no WAgame file'}, 400);
      } else if (file.size > 150000) {
        return end(res, {[file]: 'too large'}, 400);
      } else if (file.size < 10) {
        return end(res, {[file]: 'too small'}, 400);
      }
    }

    const stats = await waaas.waaas(form.files);

    await tx(async client => {
      const result = await client.query(
          `insert into game (home_id, away_id, score_home, score_away)
           values ($1, $2, $3, $4)`,
          ['']);
    });

    return end(res, {hello: 'world'});

    // TODO connection pooling and transactions

    //const result = await pool.query(
    //    `insert into game (home_id, away_id, score_home, score_away)
    //     values ($1, $2, $3, $4)`,
    //    ['']);
    // TODO game reporting as form data with replay files
    const body = await readBody(req, false);
    // TODO Persist game
    // TODO Respond to user that game is being processed
    // TODO Send to WAaaS to extract logs
    // TODO R script
    // TODO Persist ranking
    // TODO If anything after game persistence fails the game is pending
  } else if (req.url === '/api/hello-world' && req.method === 'GET') {
    const result = await pool.query(
        'SELECT $1::text as message',
        ['Hello world!']);
    console.log('from db', result.rows[0].message);; // Hello world!
    return end(res, {response: result.rows[0].message});
  } else if (req.url === '/api/users' && req.method === 'GET') {
    const result = await pool.query('select id, username from "user"');
    //const users = result.rows.map(r => ({id: r.id, username: r.username}));
    return end(res, result.rows);
  }
  return end(res, undefined, 404);
}

server.on('close', () => {
  console.info('server close - pool end')
  pool.end();
});

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

async function readBody(req, json = true) {
  return new Promise(resolve => {
    let body = '';
    req.on('data', chunk => body += chunk);
    req.on('end', () => {
      try {
        resolve(json ? JSON.parse(body) : body);
      } catch (err) {
        console.error(err);
        resolve(body);
      }
    });
  });
}

function tx(fn) {
  const client = await pool.connect();
  try {
    await client.query('BEGIN')
    await fn(client);
    await client.query('COMMIT')
  } catch (e) {
    await client.query('ROLLBACK')
    throw e
  } finally {
    client.release()
  }
}

function tearDown(msg, reason) {
  if (msg instanceof Error) {
    console.error(msg)
  }
  console.info('shutdown due to', reason)
  console.info('server close');
  server.close()
  console.info('pool end');
  pool.end();
  process.exit(0)
}

module.exports = {listen: port => server.listen(port)};

if (require.main === module) {
const port = process.argv[2] || 7171;
  console.log("port is gonna be " + port);
  server.listen(port);
}


process.on('uncaughtException', tearDown)
process.on('unhandledRejection', tearDown)
process.on('SIGTERM', tearDown)
process.on('SIGINT', tearDown)

