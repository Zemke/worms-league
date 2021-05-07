const api = {};

api.post = function (url, body) {
  return fetch(
      url,
      {
        method: 'POST',
        body: JSON.stringify(body),
        headers: {
          'content-type': 'application/json',
          'accept': 'application/json',
        }
      })
      .then(async res =>
          res.ok ? res.json() : Promise.reject(await res.json()));
};

api.get = function (url) {
  return fetch(
      url,
      {
        method: 'GET',
        headers: {
          'accept': 'application/json',
        }
      })
      .then(async res =>
          res.ok ? res.json() : Promise.reject(await res.json()));
};

api.userFromToken = function () {
  const token = window.localStorage.getItem('auth');
  if (token == null) return null;
  const base64 = token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/');
  const payload = decodeURIComponent(atob(base64)
    .split('')
    .map(c => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2))
    .join(''));
  return JSON.parse(payload).data;
}

api.fromForm = function (elements) {
  const body = Array.from(elements).reduce((acc, curr) => {
    if (curr.value && curr.name) acc[curr.name] = curr.value;
    return acc;
  }, {});
  return body;
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = api;
}

