const wl = {}

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
    const payload = Array.from(form).reduce((acc, curr) => {
      console.log(curr);
      if (curr.value && curr.name) acc[curr.name] = curr.value;
      return acc;
    }, {});
    console.log('payload', payload);
    const res = await fetch('/api/sign-up').then(res => res.json());
    console.log('res', res);
  }
}

