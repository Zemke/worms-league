import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static targets = [ 'files', 'fileInput', ];

    connect() {
        this.listFiles();
    }

    listFiles() {
        const names = Array.from(this.fileInputTarget.files).map(f => f.name);
        for (const name of names) {
          if (!name.toLowerCase().endsWith('.wagame')) {
            this.fileInputTarget.value = '';
            alert('Please upload replay WAgame files.');
            this.listFiles();
            return;
          }
        }
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
