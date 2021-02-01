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

nav.navigate = async function (url) {
  const pathname = url.pathname;
  window.history.pushState({}, null, url);
  const r = R[pathname];
  const template = await fetch(r.template).then(res => res.text());
  document.getElementById('content').innerHTML = template;
  r.controller && r.controller();
};

document.addEventListener('DOMContentLoaded', () => {
  document.addEventListener('click', e => {
    if (e.target.tagName === 'A') {
      e.preventDefault();
      nav.navigate(new URL(e.target.href));
    }
  });
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
  nav.navigate(new URL(window.location));
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
} else {
  Routing();
}
