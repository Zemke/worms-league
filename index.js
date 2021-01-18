const http = require('http');
const fs = require('fs');

const server = http.createServer((req, res) => {
  if (req.url === '/app.js') {
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
});

server.listen(7171);

