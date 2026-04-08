<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script>
(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const HostPanel = window.HostPanel || {};

    const digits = (value) => String(value || '').replace(/\D/g, '');

    const formatPhone = (value) => {
        const clean = digits(value).slice(0, 11);
        if (!clean) return '';
        if (clean.length <= 2) return clean;
        if (clean.length <= 6) return `(${clean.slice(0, 2)}) ${clean.slice(2)}`;
        if (clean.length <= 10) return `(${clean.slice(0, 2)}) ${clean.slice(2, 6)}-${clean.slice(6)}`;
        return `(${clean.slice(0, 2)}) ${clean.slice(2, 7)}-${clean.slice(7)}`;
    };

    const formatCpf = (value) => {
        const clean = digits(value).slice(0, 11);
        if (clean.length <= 3) return clean;
        if (clean.length <= 6) return `${clean.slice(0, 3)}.${clean.slice(3)}`;
        if (clean.length <= 9) return `${clean.slice(0, 3)}.${clean.slice(3, 6)}.${clean.slice(6)}`;
        return `${clean.slice(0, 3)}.${clean.slice(3, 6)}.${clean.slice(6, 9)}-${clean.slice(9)}`;
    };

    const formatCnpj = (value) => {
        const clean = digits(value).slice(0, 14);
        if (clean.length <= 2) return clean;
        if (clean.length <= 5) return `${clean.slice(0, 2)}.${clean.slice(2)}`;
        if (clean.length <= 8) return `${clean.slice(0, 2)}.${clean.slice(2, 5)}.${clean.slice(5)}`;
        if (clean.length <= 12) return `${clean.slice(0, 2)}.${clean.slice(2, 5)}.${clean.slice(5, 8)}/${clean.slice(8)}`;
        return `${clean.slice(0, 2)}.${clean.slice(2, 5)}.${clean.slice(5, 8)}/${clean.slice(8, 12)}-${clean.slice(12)}`;
    };

    const formatDocument = (value) => {
        const clean = digits(value);
        return clean.length > 11 ? formatCnpj(clean) : formatCpf(clean);
    };

    const formatCep = (value) => {
        const clean = digits(value).slice(0, 8);
        if (clean.length <= 5) return clean;
        return `${clean.slice(0, 5)}-${clean.slice(5)}`;
    };

    const formatDateInput = (value) => {
        const clean = digits(value).slice(0, 8);
        if (clean.length <= 2) return clean;
        if (clean.length <= 4) return `${clean.slice(0, 2)}/${clean.slice(2)}`;
        return `${clean.slice(0, 2)}/${clean.slice(2, 4)}/${clean.slice(4)}`;
    };

    const formatTimeInput = (value) => {
        const clean = digits(value).slice(0, 4);
        if (clean.length <= 2) return clean;
        return `${clean.slice(0, 2)}:${clean.slice(2)}`;
    };

    const formatCurrencyInput = (value) => {
        const clean = digits(value);
        const cents = Number(clean || 0) / 100;
        return cents.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    };

    const maskers = {
        phone: formatPhone,
        whatsapp: formatPhone,
        mobile: formatPhone,
        tel: formatPhone,
        cpf: formatCpf,
        cnpj: formatCnpj,
        document: formatDocument,
        cep: formatCep,
        postcode: formatCep,
        postal_code: formatCep,
        date: formatDateInput,
        time: formatTimeInput,
        money: formatCurrencyInput,
        currency: formatCurrencyInput,
    };

    const inferMask = (input) => {
        const explicit = input.dataset.mask;
        if (explicit && maskers[explicit]) {
            return explicit;
        }

        const keys = [
            input.name,
            input.id,
            input.getAttribute('x-model'),
            input.placeholder,
            input.getAttribute('aria-label'),
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        if (/whatsapp/.test(keys)) return 'whatsapp';
        if (/telefone|phone|celular|mobile|tel/.test(keys)) return 'phone';
        if (/cpf\/cnpj|cpf_cnpj|document|documento/.test(keys)) return 'document';
        if (/\bcpf\b/.test(keys)) return 'cpf';
        if (/\bcnpj\b/.test(keys)) return 'cnpj';
        if (/cep|postcode|postal_code/.test(keys)) return 'cep';
        if (/hora|time/.test(keys)) return 'time';
        if (/data|date|nascimento/.test(keys)) return 'date';
        if (/valor|preco|preço|amount|currency|money/.test(keys)) return 'money';

        return null;
    };

    const syncMaskedValue = (input, formatted) => {
        if (input.value === formatted) {
            return;
        }

        input.dataset.hpMaskInternal = '1';
        input.value = formatted;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        delete input.dataset.hpMaskInternal;
    };

    const bindMask = (input) => {
        if (input.dataset.hpMaskBound === '1') {
            return;
        }

        const mask = inferMask(input);
        if (!mask || !maskers[mask]) {
            return;
        }

        input.dataset.hpMaskBound = '1';

        if (['phone', 'whatsapp', 'mobile', 'tel', 'cpf', 'cnpj', 'document', 'cep', 'postcode', 'postal_code', 'date', 'time'].includes(mask)) {
            input.setAttribute('inputmode', 'numeric');
        }

        if (['money', 'currency'].includes(mask)) {
            input.setAttribute('inputmode', 'decimal');
        }

        const apply = () => {
            if (input.dataset.hpMaskInternal === '1') {
                return;
            }

            const formatter = maskers[inferMask(input) || mask];
            const formatted = formatter ? formatter(input.value) : input.value;
            syncMaskedValue(input, formatted);
        };

        input.addEventListener('input', apply);
        input.addEventListener('blur', apply);

        requestAnimationFrame(apply);
    };

    const fillField = (field, value) => {
        if (!field || !value) return;
        field.value = value;
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const formatBytes = (value) => {
        const bytes = Number(value || 0);
        if (!Number.isFinite(bytes) || bytes <= 0) {
            return '0 B';
        }

        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;

        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex += 1;
        }

        const decimals = size >= 100 || unitIndex === 0 ? 0 : size >= 10 ? 1 : 2;
        return `${size.toFixed(decimals).replace('.', ',')} ${units[unitIndex]}`;
    };

    const formatDuration = (value) => {
        const seconds = Math.max(0, Math.ceil(Number(value || 0)));
        if (!Number.isFinite(seconds)) {
            return '--';
        }

        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const remainingSeconds = seconds % 60;

        if (hours > 0) {
            return `${hours}h ${String(minutes).padStart(2, '0')}min`;
        }

        if (minutes > 0) {
            return `${minutes}min ${String(remainingSeconds).padStart(2, '0')}s`;
        }

        return `${remainingSeconds}s`;
    };

    const matchesAcceptRule = (file, accept = '') => {
        const rules = String(accept || '')
            .split(',')
            .map((rule) => rule.trim().toLowerCase())
            .filter(Boolean);

        if (!rules.length) {
            return true;
        }

        const fileName = String(file?.name || '').toLowerCase();
        const mimeType = String(file?.type || '').toLowerCase();

        return rules.some((rule) => {
            if (rule === '*/*') {
                return true;
            }

            if (rule.endsWith('/*')) {
                return mimeType.startsWith(rule.slice(0, -1));
            }

            if (rule.startsWith('.')) {
                return fileName.endsWith(rule);
            }

            return mimeType === rule;
        });
    };

    const normalizeResponsePayload = async (response) => {
        const contentType = response.headers.get('content-type') || '';
        const payload = contentType.includes('application/json')
            ? await response.json()
            : { message: await response.text(), ok: response.ok };

        if (typeof payload === 'object' && payload !== null && !('ok' in payload)) {
            payload.ok = response.ok;
            payload.status = response.status;
        }

        return payload;
    };

    const normalizeXhrPayload = (xhr) => {
        const contentType = xhr.getResponseHeader('content-type') || '';
        let payload;

        if (contentType.includes('application/json')) {
            try {
                payload = JSON.parse(xhr.responseText || '{}');
            } catch (error) {
                payload = { message: xhr.responseText || 'Resposta JSON inválida.' };
            }
        } else {
            payload = { message: xhr.responseText || '' };
        }

        if (typeof payload === 'object' && payload !== null && !('ok' in payload)) {
            payload.ok = xhr.status >= 200 && xhr.status < 300;
            payload.status = xhr.status;
        }

        return payload;
    };

    HostPanel.lookupCep = async (cep) => {
        const clean = digits(cep).slice(0, 8);
        if (clean.length !== 8) {
            return null;
        }

        const response = await fetch(`/api/viacep/${clean}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        if (!response.ok) {
            return null;
        }

        return response.json();
    };

    HostPanel.applyMasks = (root = document) => {
        root.querySelectorAll('input[type="text"], input[type="tel"], input[type="search"], input:not([type]), input[data-mask]').forEach(bindMask);
    };

    HostPanel.bindViaCep = (root = document) => {
        root.querySelectorAll('[data-viacep]').forEach((input) => {
            if (input.dataset.hpViacepBound === '1') {
                return;
            }

            input.dataset.hpViacepBound = '1';
            let lastLookup = '';

            const lookup = async () => {
                const clean = digits(input.value).slice(0, 8);
                if (clean.length !== 8 || clean === lastLookup) {
                    return;
                }

                lastLookup = clean;
                const scope = input.closest('form, [data-viacep-scope]') || document;
                const result = await HostPanel.lookupCep(clean);
                if (!result) {
                    return;
                }

                fillField(scope.querySelector('[data-address]'), result.logradouro || '');
                fillField(scope.querySelector('[data-neighborhood]'), result.bairro || '');
                fillField(scope.querySelector('[data-city]'), result.localidade || '');
                fillField(scope.querySelector('[data-state]'), (result.uf || '').toUpperCase());
                fillField(scope.querySelector('[data-ibge]'), result.ibge || '');
            };

            input.addEventListener('blur', lookup);
            input.addEventListener('input', () => {
                if (digits(input.value).length === 8) {
                    lookup();
                }
            });
        });
    };

    HostPanel.csrfToken = csrfToken;
    HostPanel.formatBytes = formatBytes;
    HostPanel.formatDuration = formatDuration;

    HostPanel.fetch = async (url, options = {}) => {
        const headers = {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            ...(options.body && !(options.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
            ...(options.headers || {}),
        };

        const response = await fetch(url, { ...options, headers });
        return normalizeResponsePayload(response);
    };

    HostPanel.upload = ({ url, method = 'POST', body = null, headers = {}, onProgress = null } = {}) => new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        const startedAt = Date.now();

        xhr.open(method, url, true);
        Object.entries({
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            ...headers,
        }).forEach(([key, value]) => {
            xhr.setRequestHeader(key, value);
        });

        if (xhr.upload && typeof onProgress === 'function') {
            xhr.upload.onprogress = (event) => {
                const elapsedSeconds = Math.max((Date.now() - startedAt) / 1000, 0.1);
                const loaded = Number(event.loaded || 0);
                const total = Number(event.total || 0);
                const speedBytes = loaded / elapsedSeconds;
                const remainingSeconds = total > loaded && speedBytes > 0
                    ? (total - loaded) / speedBytes
                    : 0;

                onProgress({
                    loaded,
                    total,
                    percent: total > 0 ? (loaded / total) * 100 : 0,
                    elapsedSeconds,
                    speedBytes,
                    remainingSeconds,
                });
            };
        }

        xhr.onload = () => resolve(normalizeXhrPayload(xhr));
        xhr.onerror = () => reject(new Error('Falha de rede durante o envio.'));
        xhr.onabort = () => reject(new Error('Envio cancelado.'));
        xhr.send(body);
    });

    HostPanel.createUploadState = (config = {}) => ({
        input: null,
        files: [],
        dragging: false,
        isUploading: false,
        uploadProgress: 0,
        uploadedBytes: 0,
        totalBytes: 0,
        elapsedSeconds: 0,
        remainingSeconds: 0,
        speedBytes: 0,
        error: '',
        accept: config.accept || '',
        multiple: config.multiple !== false,
        maxFiles: Number(config.maxFiles || 0),
        maxFileSize: Number(config.maxFileSize || 0),

        attachInput(input) {
            if (!input) {
                this.input = null;
                return;
            }

            this.input = input;

            if (this.accept) {
                input.setAttribute('accept', this.accept);
            }

            if (this.multiple) {
                input.setAttribute('multiple', 'multiple');
            } else {
                input.removeAttribute('multiple');
            }

            this.syncInput();
        },

        syncInput() {
            if (!this.input || typeof DataTransfer === 'undefined') {
                return;
            }

            const transfer = new DataTransfer();
            this.files.forEach((file) => transfer.items.add(file));
            this.input.files = transfer.files;
        },

        setError(message = '') {
            this.error = message;
            return { ok: !message, message };
        },

        addFiles(fileList, { replace = false } = {}) {
            const incoming = Array.from(fileList || []);
            let nextFiles = replace ? [] : [...this.files];

            for (const file of incoming) {
                if (!matchesAcceptRule(file, this.accept)) {
                    return this.setError(`Arquivo inválido: ${file.name}.`);
                }

                if (this.maxFileSize && file.size > this.maxFileSize) {
                    return this.setError(`O arquivo ${file.name} excede o limite de ${formatBytes(this.maxFileSize)}.`);
                }

                const duplicate = nextFiles.some((candidate) => (
                    candidate.name === file.name
                    && candidate.size === file.size
                    && candidate.lastModified === file.lastModified
                ));

                if (!duplicate) {
                    nextFiles.push(file);
                }
            }

            if (!this.multiple) {
                nextFiles = nextFiles.slice(-1);
            }

            if (this.maxFiles && nextFiles.length > this.maxFiles) {
                return this.setError(`Selecione no máximo ${this.maxFiles} arquivo(s).`);
            }

            this.files = nextFiles;
            this.error = '';
            this.syncInput();
            return { ok: true, message: '' };
        },

        replaceFiles(fileList) {
            return this.addFiles(fileList, { replace: true });
        },

        onInputChange(event) {
            const result = this.replaceFiles(event?.target?.files || []);
            if (!result.ok && result.message) {
                HostPanel.toast(result.message, 'warning');
            }
        },

        handleDragOver(event) {
            event.preventDefault();
            this.dragging = true;
        },

        handleDragLeave(event) {
            if (!event.currentTarget.contains(event.relatedTarget)) {
                this.dragging = false;
            }
        },

        handleDrop(event) {
            event.preventDefault();
            this.dragging = false;
            const result = this.addFiles(event?.dataTransfer?.files || []);
            if (!result.ok && result.message) {
                HostPanel.toast(result.message, 'warning');
            }
        },

        openPicker() {
            this.input?.click();
        },

        removeFile(index) {
            this.files.splice(index, 1);
            this.files = [...this.files];
            this.syncInput();
        },

        clearFiles() {
            this.files = [];
            this.error = '';
            if (this.input) {
                this.input.value = '';
            }
            this.syncInput();
        },

        resetProgress() {
            this.uploadProgress = 0;
            this.uploadedBytes = 0;
            this.totalBytes = 0;
            this.elapsedSeconds = 0;
            this.remainingSeconds = 0;
            this.speedBytes = 0;
        },

        startUpload() {
            this.isUploading = true;
            this.resetProgress();
            this.totalBytes = this.totalSize();
        },

        updateProgress(progress) {
            this.uploadProgress = Number(progress?.percent || 0);
            this.uploadedBytes = Number(progress?.loaded || 0);
            this.totalBytes = Number(progress?.total || 0);
            this.elapsedSeconds = Number(progress?.elapsedSeconds || 0);
            this.remainingSeconds = Number(progress?.remainingSeconds || 0);
            this.speedBytes = Number(progress?.speedBytes || 0);
        },

        finishUpload() {
            this.isUploading = false;
            this.uploadProgress = this.totalBytes > 0 ? 100 : this.uploadProgress;
            this.remainingSeconds = 0;
        },

        failUpload() {
            this.isUploading = false;
        },

        hasFiles() {
            return this.files.length > 0;
        },

        totalSize() {
            return this.files.reduce((total, file) => total + Number(file.size || 0), 0);
        },

        totalSizeLabel() {
            return formatBytes(this.totalSize());
        },

        uploadedLabel() {
            return formatBytes(this.uploadedBytes);
        },

        totalBytesLabel() {
            return formatBytes(this.totalBytes);
        },

        progressLabel() {
            return `${Math.round(this.uploadProgress)}%`;
        },

        speedLabel() {
            return this.speedBytes > 0 ? `${formatBytes(this.speedBytes)}/s` : '--';
        },

        remainingLabel() {
            if (!this.isUploading || !this.totalBytes) {
                return '--';
            }

            return formatDuration(this.remainingSeconds);
        },

        elapsedLabel() {
            return formatDuration(this.elapsedSeconds);
        },
    });

    HostPanel.toast = (message, type = 'success', options = {}) => {
        if (!message) return;

        if (typeof Toastify !== 'function') {
            console[type === 'danger' || type === 'error' ? 'error' : 'log'](message);
            return;
        }

        const backgrounds = {
            success: 'linear-gradient(135deg, #059669, #10b981)',
            danger: 'linear-gradient(135deg, #dc2626, #ef4444)',
            error: 'linear-gradient(135deg, #dc2626, #ef4444)',
            warning: 'linear-gradient(135deg, #d97706, #f59e0b)',
            info: 'linear-gradient(135deg, #2563eb, #3b82f6)',
        };

        Toastify({
            text: message,
            duration: options.duration || 4500,
            gravity: options.gravity || 'bottom',
            position: options.position || 'right',
            stopOnFocus: true,
            close: options.close !== false,
            className: `hp-toast-${type}`,
            style: {
                background: backgrounds[type] || backgrounds.info,
            },
        }).showToast();
    };

    HostPanel.confirm = async (config = {}) => {
        const options = typeof config === 'string' ? { text: config } : config;

        if (typeof Swal === 'undefined') {
            return window.confirm(options.text || options.title || 'Deseja continuar?');
        }

        const result = await Swal.fire({
            title: options.title || 'Confirmar acao',
            text: options.text || 'Deseja continuar?',
            icon: options.icon || 'warning',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            confirmButtonText: options.confirmButtonText || 'Sim, continuar',
            cancelButtonText: options.cancelButtonText || 'Cancelar',
            customClass: {
                popup: 'hp-swal-popup',
                confirmButton: 'hp-swal-confirm',
                cancelButton: 'hp-swal-cancel',
            },
            confirmButtonColor: options.confirmButtonColor || '#1d4ed8',
            cancelButtonColor: options.cancelButtonColor || '#64748b',
        });

        return result.isConfirmed;
    };

    HostPanel.alert = async (config = {}) => {
        const options = typeof config === 'string' ? { text: config } : config;

        if (typeof Swal === 'undefined') {
            window.alert(options.text || options.title || 'Aviso');
            return;
        }

        return Swal.fire({
            title: options.title || 'Aviso',
            text: options.text || '',
            icon: options.icon || 'info',
            confirmButtonText: options.confirmButtonText || 'Fechar',
            customClass: {
                popup: 'hp-swal-popup',
                confirmButton: 'hp-swal-confirm',
            },
            confirmButtonColor: options.confirmButtonColor || '#1d4ed8',
        });
    };

    HostPanel.copyText = async (text, successMessage = 'Copiado com sucesso.') => {
        if (!text) return false;

        try {
            await navigator.clipboard.writeText(text);
            HostPanel.toast(successMessage, 'success');
            return true;
        } catch (error) {
            HostPanel.toast('Nao foi possivel copiar agora.', 'danger');
            return false;
        }
    };

    HostPanel.initEditor = (el, expression, evaluate) => {
        const $el = $(el);
        const config = {
            placeholder: el.placeholder || '',
            tabsize: 2,
            height: el.dataset.height || 300,
            lang: 'pt-BR',
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture']],
                ['view', ['fullscreen', 'codeview']]
            ],
            callbacks: {
                onChange: function(content) {
                    evaluate(`${expression} = \`${content.replace(/`/g, '\\`').replace(/\$/g, '\\$')}\``);
                }
            }
        };
        $el.summernote(config);
    };

    window.HostPanel = HostPanel;

    const boot = () => {
        HostPanel.applyMasks(document);
        HostPanel.bindViaCep(document);

        // Registro da diretiva x-editor
        if (window.Alpine) {
            window.Alpine.directive('editor', (el, { expression }, { evaluate, effect }) => {
                const getVal = () => {
                    let val = '';
                    evaluate(v => val = v);
                    return val;
                };

                HostPanel.initEditor(el, expression, evaluate);

                effect(() => {
                    const current = getVal();
                    if ($(el).summernote('code') !== current) {
                        $(el).summernote('code', current || '');
                    }
                });
            });
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
</script>
