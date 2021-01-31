const index = require('./api/index.js');

const port = process.argv[2] || 7171;
console.log("port is gonna be " + port);
index.listen(port);

