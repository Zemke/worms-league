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

(() => {
  Routing();
})();

function SignUp() {
  wl.submit = async form => {
    const body = Array.from(form).reduce((acc, curr) => {
      console.log(curr);
      if (curr.value && curr.name) acc[curr.name] = curr.value;
      return acc;
    }, {});
    console.log('body', body);
    const response = await api.post('/api/sign-up', body);
    console.log('response', response);
  }
}


