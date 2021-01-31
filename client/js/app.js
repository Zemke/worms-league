const wl = {}

const api = {};

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

async function Routing() {
  const path = window.location.pathname;
  let res;
  if (path === "/") {
    res = await (await fetch("/tpl/home.html")).text();
  } else if (path === "/sign-up") {
    res = await (await fetch("/tpl/sign-up.html")).text();
    ctrl = SignUp();
  }
  document.getElementById('content').innerHTML = res;
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
