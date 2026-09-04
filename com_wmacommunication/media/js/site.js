// WMA Communication - Site JS

document.addEventListener('DOMContentLoaded', function () {

    function getMessages(form) {
        var defaults = {
            required: 'Campo obbligatorio',
            email: 'Indirizzo email non valido',
            url: 'URL non valido',
            tel: 'Numero di telefono non valido',
            number: 'Valore numerico non valido',
            min: 'Valore minimo: %s',
            max: 'Valore massimo: %s',
            filetype: 'Tipo di file non consentito',
            filesize: 'File troppo grande (max %s MB)'
        };
        try {
            var parsed = JSON.parse(form.dataset.wmaMessages || '{}');
            Object.keys(defaults).forEach(function (k) {
                if (parsed[k]) defaults[k] = parsed[k];
            });
        } catch (e) { /* usa i default */ }
        return defaults;
    }

    function wrapperOf(el) {
        return el.closest ? el.closest('.wma-cf-field') : null;
    }

    function setError(wrapper, msg) {
        if (!wrapper) return;
        wrapper.classList.add('wma-cf-field--error');
        wrapper.classList.remove('wma-cf-error'); // classe server: sostituita da quella live
        var span = wrapper.querySelector('.wma-cf-error');
        if (!span) {
            span = document.createElement('span');
            span.className = 'wma-cf-error';
            wrapper.appendChild(span);
        }
        span.textContent = msg;
    }

    function clearError(wrapper) {
        if (!wrapper) return;
        wrapper.classList.remove('wma-cf-field--error', 'wma-cf-error');
        var span = wrapper.querySelector('.wma-cf-error');
        if (span) span.remove();
    }

    // Messaggio d'errore di FORMATO (o null). I campi vuoti non danno errore qui:
    // se ne occupa il controllo "obbligatorio".
    function formatError(input, msgs) {
        var v = (input.value || '').trim();
        if (v === '') return null;

        if (input.type === 'email') {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? null : msgs.email;
        }
        if (input.type === 'url') {
            return /^https?:\/\/[^\s]+\.[^\s]+/i.test(v) ? null : msgs.url;
        }
        if (input.type === 'tel') {
            return /^[0-9+().\/\s-]{5,40}$/.test(v) ? null : msgs.tel;
        }
        if (input.type === 'number') {
            var n = parseFloat(v.replace(',', '.'));
            if (isNaN(n) || !/^-?[0-9]+([.,][0-9]+)?$/.test(v)) return msgs.number;
            var min = input.getAttribute('min');
            var max = input.getAttribute('max');
            if (min !== null && min !== '' && n < parseFloat(min)) return msgs.min.replace('%s', min);
            if (max !== null && max !== '' && n > parseFloat(max)) return msgs.max.replace('%s', max);
        }
        return null;
    }

    // Messaggio d'errore per un campo file (o null): estensione fuori lista o file troppo grande.
    function fileError(input, msgs) {
        if (!input.files || input.files.length === 0) return null;
        var file = input.files[0];

        var accept = (input.dataset.wmaAccept || '').toLowerCase().split(',').filter(Boolean);
        if (accept.length) {
            var dot = file.name.lastIndexOf('.');
            var ext = dot >= 0 ? file.name.slice(dot + 1).toLowerCase() : '';
            if (accept.indexOf(ext) === -1) return msgs.filetype;
        }

        var maxMb = parseInt(input.dataset.wmaMaxsize || '0', 10);
        if (maxMb > 0 && file.size > maxMb * 1024 * 1024) {
            return msgs.filesize.replace('%s', maxMb);
        }
        return null;
    }

    document.querySelectorAll('.wma-cf-form').forEach(function (form) {

        var messages = getMessages(form);

        function requiredError(input) {
            if (input.type === 'radio') {
                var group = form.querySelectorAll('input[type="radio"][name="' + (window.CSS && CSS.escape ? CSS.escape(input.name) : input.name) + '"]');
                var picked = Array.prototype.some.call(group, function (r) { return r.checked; });
                return picked ? null : messages.required;
            }
            return input.value.trim() === '' ? messages.required : null;
        }

        // Valida un singolo campo (input/select/textarea). Ritorna true se valido.
        function validateField(input) {
            var wrapper = wrapperOf(input);
            if (!wrapper || input.dataset.wmaPrivacy) return true;

            var msg = null;

            if (input.type === 'file') {
                var noFile = !input.files || input.files.length === 0;
                if (input.hasAttribute('required') && noFile) {
                    msg = messages.required;
                } else if (!noFile) {
                    msg = fileError(input, messages);
                }
            } else {
                if (input.hasAttribute('required')) msg = requiredError(input);
                if (!msg) msg = formatError(input, messages);
            }

            if (msg) { setError(wrapper, msg); return false; }
            clearError(wrapper);
            return true;
        }

        // Valida un gruppo di checkbox con data-wma-required. Ritorna true se valido.
        function validateCheckboxGroup(group) {
            var wrapper = wrapperOf(group);
            if (!wrapper) return true;
            var picked = group.querySelector('input[type="checkbox"]:checked');
            if (!picked) { setError(wrapper, messages.required); return false; }
            clearError(wrapper);
            return true;
        }

        // --- Privacy: disabilita pulsante se non spuntata ---
        var privacyCheckbox = form.querySelector('[data-wma-privacy]');
        var submitBtn = form.querySelector('.wma-cf-submit');

        if (privacyCheckbox && submitBtn) {
            var updateSubmitState = function () {
                submitBtn.disabled = !privacyCheckbox.checked;
            };
            updateSubmitState();
            privacyCheckbox.addEventListener('change', updateSubmitState);
        }

        // --- Validazione "al volo": alla perdita del focus + mentre si corregge ---
        form.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (el.dataset.wmaPrivacy || el.type === 'submit' || el.type === 'button') return;

            if (el.type === 'file') {
                el.addEventListener('change', function () { validateField(el); });
                return;
            }

            var isChoice = (el.type === 'radio' || el.type === 'checkbox');
            var group = isChoice ? el.closest('.wma-cf-choices') : null;

            el.addEventListener('blur', function () {
                if (group && group.hasAttribute('data-wma-required')) {
                    validateCheckboxGroup(group);
                } else if (!isChoice) {
                    validateField(el);
                } else if (el.type === 'radio' && el.hasAttribute('required')) {
                    validateField(el);
                }
            });

            var liveEvent = (isChoice || el.tagName === 'SELECT') ? 'change' : 'input';
            el.addEventListener(liveEvent, function () {
                if (group && group.hasAttribute('data-wma-required')) {
                    if (wrapperOf(group).classList.contains('wma-cf-field--error')) validateCheckboxGroup(group);
                    return;
                }
                var wrapper = wrapperOf(el);
                if (wrapper && (wrapper.classList.contains('wma-cf-field--error') || wrapper.classList.contains('wma-cf-error'))) {
                    validateField(el);
                }
            });
        });

        // --- Validazione completa al submit ---
        form.addEventListener('submit', function (e) {
            var valid = true;
            var radioGroupsSeen = {};

            form.querySelectorAll('input, select, textarea').forEach(function (el) {
                if (el.dataset.wmaPrivacy || el.type === 'submit' || el.type === 'button') return;
                if (el.type === 'checkbox') return; // gestiti come gruppo
                if (el.type === 'radio') {
                    if (radioGroupsSeen[el.name]) return;
                    radioGroupsSeen[el.name] = true;
                }
                if (!validateField(el)) valid = false;
            });

            form.querySelectorAll('.wma-cf-choices[data-wma-required]').forEach(function (group) {
                if (!validateCheckboxGroup(group)) valid = false;
            });

            if (!valid) {
                e.preventDefault();
                var firstError = form.querySelector('.wma-cf-field--error, .wma-cf-field.wma-cf-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    var focusable = firstError.querySelector('input, select, textarea');
                    if (focusable) focusable.focus();
                }
            }
        });

        // Scroll al primo errore restituito dal server (dopo validazione backend)
        var serverError = form.querySelector('.wma-cf-field.wma-cf-error');
        if (serverError) {
            setTimeout(function () {
                serverError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                var el = serverError.querySelector('input, select, textarea');
                if (el) el.focus();
            }, 300);
        }
    });
});
