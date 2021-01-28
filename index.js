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

//from db Result {
//  command: 'INSERT',
//  rowCount: 1,
//  oid: 0,
//  rows: [],
//  fields: [],
//  _parsers: undefined,
//  _types: TypeOverrides {
//    _types: {
//      getTypeParser: [Function: getTypeParser],
//      setTypeParser: [Function: setTypeParser],
//      arrayParser: [Object],
//      builtins: [Object]
//    },
//    text: {},
//    binary: {}
//  },
//  RowCtor: null,
//  rowAsArray: false
//}

server.listen(7171);

