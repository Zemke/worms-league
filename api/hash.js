const bcrypt = require('bcrypt');

const HASH_ROUNDS = 10;

const api = {};

api.hash = async function(plain) {
  return bcrypt.hash(body.password, HASH_ROUNDS);
};

api.compare = async function(plain, hash) {
  return bcrypt.compare(plain, hash);
};

module.exports = api;

