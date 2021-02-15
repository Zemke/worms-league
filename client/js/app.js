const wl = {}

const R = {
  '/sign-up': {
    controller: SignUp,
    template: '/tpl/sign-up.html',
  },
  '/account': {
    controller: Account,
    template: '/tpl/account.html',
  },
  '/': {
    template: '/tpl/home.html',
  },
};

const api = {};

const nav = {};

nav.setContent = function (content) {
  document.getElementById('content').innerHTML = content;
}

nav.loadTemplate = async function (url) {
  const r = R[url.pathname];
  if (r == null) {
    document.getElementById('content').innerHTML = 'This page does not exist.';
    return;
  }
  const template = await fetch(r.template).then(res => res.text());
  nav.setContent(template);
  r.controller && r.controller();
}

nav.navigate = async function (url) {
  const pathname = url.pathname;
  window.history.pushState({}, null, url);
  nav.loadTemplate(url);
  updateNav(url);
};

document.addEventListener('DOMContentLoaded', () => {
  // Link clicks
  document.addEventListener('click', e => {
    const aElem = e.target.closest('a');
    if (aElem != null) {
      e.preventDefault();
      nav.navigate(new URL(aElem.href));
    }
  });
  // Browser back/forward buttons
  window.onpopstate = e => {
    nav.loadTemplate(new URL(e.target.location));
  };

  // TODO Start HTTP requests earlier and don't wait for DOMContentLoaded.
  Routing();
});

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

api.signIn = function (username) {
  const signInElem = document.getElementById('sign-in');
  if (signInElem) signInElem.style.display = 'none';
  const accountElem = document.getElementById('account');
  accountElem.innerHTML = `<a href="/account">${username}</a>`;
  const signOutElem = document.getElementById("sign-out");
  if (signOutElem) signOutElem.style.display = 'inline';
};

api.signOut = function () {
  const signInElem = document.getElementById('sign-in');
  if (signInElem) signInElem.style.display = 'block';
  const accountElem = document.getElementById('account');
  accountElem.innerHTML = '';
  const signOutElem = document.getElementById("sign-out");
  if (signOutElem) signOutElem.style.display = 'none';
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

function Routing() {
  const url = new URL(window.location);
  nav.loadTemplate(url);
  updateNav(url);
  const user = api.userFromToken();
  console.log('user', user);
  user == null ? api.signOut() : api.signIn(user.username);
}

function signIn(form) {
  api.post('/api/sign-in', fromForm(form.elements)).then(res => {
    window.localStorage.setItem('auth', res.token);
    const {username} = api.userFromToken();
    api.signIn(username);
  }).catch(({err}) => toast(err));
  return false;
}

function signOut() {
  window.localStorage.removeItem('auth');
  api.signOut();
}

function updateNav(url) {
  document.querySelectorAll(`nav.nav a.nav-item`)
      .forEach(i => i.classList.remove('active'));
  const activeNavItem = document.querySelector(
      `nav.nav .nav-item[href='${url.pathname}']`)
  activeNavItem && activeNavItem.classList.add('active');
}

function SignUp() {
  wl.submit = form => {
    api.post('/api/sign-up', fromForm(form.elements)).then(res => {
      nav.setContent(`
          <p class="lead">
            Welcome to the <b>Worms League</b>!
            You are now signed in.
          </p>`);
      window.localStorage.setItem('auth', res.token);
    }).catch(({err}) => toast(err));
    return false;
  }
}

function Account() {
  const {username} = api.userFromToken();
  document.getElementById('username').innerHTML = username;
}

function Report() {
  wl.submit = async form => {
    const body = fromForm(form.elements);
    const response = await api.post('/api/sign-up', body);
  }
}

function fromForm(elements) {
  const body = Array.from(elements).reduce((acc, curr) => {
    if (curr.value && curr.name) acc[curr.name] = curr.value;
    return acc;
  }, {});
  return body;
}

function toast(message) {
  alert(message); // TODO display actual toast
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    fromForm
  };
}

