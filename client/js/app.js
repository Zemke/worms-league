const wl = {}


const R = {
  '/sign-up': {
    controller: SignUp,
    template: '/tpl/sign-up.html',
  },
  '/': {
    template: '/tpl/home.html',
  }
};

const api = {};

const nav = {};

nav.loadTemplate = async function (url) {
  const r = R[url.pathname];
  if (r == null) return;
  const template = await fetch(r.template).then(res => res.text());
  document.getElementById('content').innerHTML = template;
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

api.post = async (url, body) => {
    return await fetch(
        url,
        {
          method: 'POST',
          body: JSON.stringify(body),
          headers: {
            'content-type': 'application/json',
            'accept': 'application/json',
          }
        })
        .then(res => res.json());
};

function Routing() {
  const url = new URL(window.location);
  nav.loadTemplate(url);
  updateNav(url);
}

function updateNav(url) {
  document.querySelectorAll(`nav.nav a.nav-item`)
      .forEach(i => i.classList.remove('active'));
  const activeNavItem = document.querySelector(`nav.nav .nav-item[href='${url.pathname}']`)
  activeNavItem && activeNavItem.classList.add('active');
}

function SignUp() {
  wl.submit = async form => {
    const body = fromForm(form.elements);
    const response = await api.post('/api/sign-up', body);
  }
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

if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    fromForm
  };
}

