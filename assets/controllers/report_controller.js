import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static targets = [ 'files', ];

    connect() {
    }

    greet() {
        console.log('called');
    }

    onFileChange(e) {
        const names = Array.from(e.target.files).map(f => f.name);
        console.log(names);
        console.log(this.filesTarget);
        this.filesTarget.innerHTML = '';
        names.map(n => {
            const li = document.createElement('li');
            li.textContent = n;
            return li
        }).forEach(li => {
            this.filesTarget.appendChild(li);
        });
    }
}
