document.addEventListener('click', e => {
    document.querySelectorAll('.dropdown.active').forEach(elem => {
        elem.classList.remove('active');
    });
});

document.querySelectorAll('[data-dropdown-action]').forEach(actionElem => {
    actionElem.addEventListener('click', e => setTimeout(() =>
        actionElem
            .closest('.dropdown-wrapper')
            .querySelector('[data-dropdown-target]')
            .classList.add('active')));
});

