document.addEventListener('DOMContentLoaded', function () {
    const root = document.documentElement;
    const themeButton = document.querySelector('[data-theme-toggle]');
    const toastStack = document.querySelector('[data-toast-stack]');
    const dismissToast = function (toast) {
        if (toast) toast.remove();
    };
    const startToastTimer = function (toast) {
        window.setTimeout(function () { dismissToast(toast); }, 5000);
    };
    const showToast = function (text, type) {
        if (!toastStack) return;
        const toast = document.createElement('div');
        toast.className = 'toast toast-' + (type === 'error' ? 'error' : 'success');
        toast.setAttribute('data-toast', '');
        const message = document.createElement('span');
        message.textContent = text;
        const close = document.createElement('button');
        close.className = 'toast-close';
        close.type = 'button';
        close.setAttribute('aria-label', 'Close notification');
        close.innerHTML = '&times;';
        close.addEventListener('click', function () { dismissToast(toast); });
        toast.appendChild(message);
        toast.appendChild(close);
        toastStack.appendChild(toast);
        startToastTimer(toast);
    };
    document.querySelectorAll('[data-toast]').forEach(function (toast) {
        const close = toast.querySelector('[data-toast-close]');
        if (close) close.addEventListener('click', function () { dismissToast(toast); });
        startToastTimer(toast);
    });
    const savedTheme = window.localStorage.getItem('panel-theme') || 'dark';
    const applyTheme = function (theme) {
        root.dataset.theme = theme;
        if (themeButton) {
            const nextTheme = theme === 'dark' ? 'light' : 'dark';
            const label = 'Switch to ' + nextTheme + ' theme';
            themeButton.setAttribute('aria-label', label);
            themeButton.setAttribute('title', label);
        }
    };
    applyTheme(savedTheme);
    if (themeButton) {
        themeButton.addEventListener('click', function () {
            const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
            window.localStorage.setItem('panel-theme', next);
            applyTheme(next);
        });
    }

    document.querySelectorAll('.switch input').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            const state = toggle.parentElement.querySelector('.switch-state');
            if (state) state.textContent = toggle.checked ? 'Enabled' : 'Disabled';
        });
    });

    const menu = document.querySelector('[data-sidebar]');
    const toggle = document.querySelector('[data-menu-toggle]');
    if (toggle && menu) {
        toggle.addEventListener('click', function () { menu.classList.toggle('open'); });
    }

    document.querySelectorAll('[data-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            const text = button.dataset.copy;
            const fallbackCopy = function () {
                const input = document.createElement('textarea');
                input.value = text;
                input.setAttribute('readonly', '');
                input.style.position = 'fixed';
                input.style.opacity = '0';
                document.body.appendChild(input);
                input.select();
                const copied = document.execCommand('copy');
                input.remove();
                if (copied) showToast('Server address copied.', 'success');
                else showToast('Unable to copy server address.', 'error');
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function () {
                    showToast('Server address copied.', 'success');
                }).catch(fallbackCopy);
            } else {
                fallbackCopy();
            }
        });
    });
    document.querySelectorAll('[data-download]').forEach(function (link) {
        link.addEventListener('click', function () {
            showToast('Backup download started.', 'success');
        });
    });

    const consoleBox = document.querySelector('[data-live-console]');
    if (consoleBox) {
        const loadConsole = function () {
            fetch('api_console.php').then(function (response) { return response.text(); }).then(function (text) {
                consoleBox.textContent = text;
                consoleBox.scrollTop = consoleBox.scrollHeight;
            });
        };
        loadConsole();
        window.setInterval(loadConsole, 3000);
    }

    const fileInput = document.getElementById('worldFile');
    const dropZone = document.getElementById('dropZone');
    if (!fileInput || !dropZone) return;

    const selected = document.getElementById('selectedFile');
    const bar = document.getElementById('uploadBar');
    const log = document.getElementById('uploadLog');
    const button = document.getElementById('uploadBtn');
    const showFile = function () {
        const file = fileInput.files[0];
        selected.textContent = file ? 'Selected: ' + file.name + ' (' + Math.ceil(file.size / 1024 / 1024) + ' MB)' : '';
    };
    const writeLog = function (text) {
        if (log.textContent === 'Ready for a world backup.') log.textContent = '';
        log.textContent += text + '\n';
        log.scrollTop = log.scrollHeight;
    };
    const requestText = async function (url, options) {
        const response = await fetch(url, options);
        const text = await response.text();
        if (!response.ok) throw new Error(text || 'HTTP ' + response.status);
        return text;
    };

    dropZone.addEventListener('click', function () { fileInput.click(); });
    dropZone.addEventListener('dragover', function (event) { event.preventDefault(); dropZone.classList.add('drag'); });
    dropZone.addEventListener('dragleave', function () { dropZone.classList.remove('drag'); });
    dropZone.addEventListener('drop', function (event) {
        event.preventDefault();
        dropZone.classList.remove('drag');
        fileInput.files = event.dataTransfer.files;
        showFile();
    });
    fileInput.addEventListener('change', showFile);
    button.addEventListener('click', async function () {
        const file = fileInput.files[0];
        if (!file) {
            writeLog('Choose a backup file first.');
            return showToast('Choose a backup file first.', 'error');
        }
        if (!/\.(zip|tar\.gz)$/i.test(file.name)) {
            writeLog('Only .zip and .tar.gz files are supported.');
            return showToast('Unsupported backup file type.', 'error');
        }
        const size = 5 * 1024 * 1024;
        const total = Math.ceil(file.size / size);
        log.textContent = '';
        button.disabled = true;
        try {
            for (let index = 0; index < total; index++) {
                const form = new FormData();
                form.append('chunk', file.slice(index * size, (index + 1) * size));
                form.append('name', file.name);
                form.append('index', index);
                form.append('total', total);
                writeLog(await requestText('upload.php', {method: 'POST', body: form}));
                bar.style.width = Math.floor(((index + 1) / total) * 70) + '%';
            }
            writeLog(await requestText('merge.php?name=' + encodeURIComponent(file.name) + '&total=' + total));
            bar.style.width = '84%';
            writeLog(await requestText('restore.php'));
            bar.style.width = '100%';
            writeLog('Restore complete.');
            showToast('World uploaded and restored successfully.', 'success');
        } catch (error) {
            writeLog('Upload stopped: ' + error.message);
            showToast('Upload or restore failed.', 'error');
        } finally {
            button.disabled = false;
        }
    });
});
