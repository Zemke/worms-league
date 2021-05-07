// TODO namespace

function updateTopBar() {
  const auth = api.userFromToken();
  if (auth == null) {
    const signInElem = document.getElementById('sign-in');
    if (signInElem) signInElem.style.display = 'block';
    const accountElem = document.getElementById('account');
    accountElem.innerHTML = '';
    const signOutElem = document.getElementById("sign-out");
    if (signOutElem) signOutElem.style.display = 'none';
  } else {
    const username = auth.username;
    const signInElem = document.getElementById('sign-in');
    if (signInElem) signInElem.style.display = 'none';
    const accountElem = document.getElementById('account');
    accountElem.innerHTML = `<a href="/account">${username}</a>`;
    const signOutElem = document.getElementById("sign-out");
    if (signOutElem) signOutElem.style.display = 'inline';
  }
}

function signIn(form) {
  api.post('/api/sign-in', api.fromForm(form.elements)).then(res => {
    window.localStorage.setItem('auth', res.token);
    updateTopBar();
  }).catch(({err}) => toast(err));
  return false;
}

function signOut() {
  window.localStorage.removeItem('auth');
  updateTopBar();
}

function toast(message) {
  alert(message); // TODO display actual toast
}

