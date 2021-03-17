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

  // running the script tag JS in the template file
  const templateContainer = document.getElementById('content');
  Array.from(templateContainer.querySelectorAll("script")).forEach(oldScript => {
    const newScript = document.createElement("script");
    Array.from(oldScript.attributes)
        .forEach(attr => newScript.setAttribute(attr.name, attr.value));
    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
    oldScript.parentNode.replaceChild(newScript, oldScript);
  });
}

nav.navigate = async function (url) {
  const pathname = url.pathname;
  window.history.pushState({}, null, url);
  nav.loadTemplate(url);
  updateNav(url);
};

const R = {
  '/sign-up': {
    template: '/tpl/sign-up.html',
  },
  '/account': {
    template: '/tpl/account.html',
  },
  '/report': {
    template: '/tpl/report.html',
  },
  '/': {
    template: '/tpl/home.html',
  },
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

function updateNav(url) {
  document.querySelectorAll(`nav.nav a.nav-item`)
      .forEach(i => i.classList.remove('active'));
  const activeNavItem = document.querySelector(
      `nav.nav .nav-item[href='${url.pathname}']`)
  activeNavItem && activeNavItem.classList.add('active');
}

function Routing() {
  const url = new URL(window.location);
  nav.loadTemplate(url);
  updateNav(url);
  const user = api.userFromToken();
  console.log('user', user);
  updateTopBar();
}

