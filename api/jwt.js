const jwt = require('jsonwebtoken');

function jwtSign(data) {
  let secret;
  if (process.env.JWT_SECRET == null) {
    console.warn("No JWT key specified, using an unsafe default.");
    secret = 'unsafedefault';
  } else {
    secret = process.env.JWT_SECRET;
  }
  return new Promise((resolve, reject) => {
    jwt.sign({data}, secret, (err, token) => {
      if (err != null) {
        reject(err);
      } else {
        resolve(token);
      }
    });
  });
}

module.exports = {jwtSign};

