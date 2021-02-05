const api = {};

api.email = email => {
  const idx = email.indexOf('@');
  return idx !== 0 && idx < email.length - 1;
};

api.req = s => s != null;

api.minLen = (s, len) => s.length >= len;
api.maxLen = (s, len) => s.length <= len;

api.alphaNum = s => s.match(/^[a-z0-9]*$/i) != null;

api.regex = (s, regex) => s.match(regex) != null;

module.exports = api;
