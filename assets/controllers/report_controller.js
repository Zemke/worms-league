import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static targets = [ 'files', 'fileInput', ];

    connect() {
        this.listFiles();
    }

    greet() {
        console.log('called');
    }

    listFiles() {
        const names = Array.from(this.fileInputTarget.files).map(f => f.name);
        console.log(names);
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
