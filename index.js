const server = require('./api/server.js');

const port = process.argv[2] || 7171;
console.log("port is gonna be " + port);
server.listen(port);

