async function Routing() {
  const path = window.location.pathname;
  let res;
  if (path === "/") {
    res = await (await fetch("/tpl/home.html")).text();
  } else if (path === "/sign-up") {
    res = await (await fetch("/tpl/sign-up.html")).text();
  }
  document.getElementById('content').innerHTML = res;
}

(() => {
  Routing();
})();

