/**
 * @package     Wma.Component.Wmacommunication
 * @subpackage  com_wmacommunication
 *
 * @author      WMA Web Maker Agency
 * @copyright   (C) 2026 WMA Web Maker Agency
 * @license     GNU General Public License version 2 or later
 * @link        https://www.webmakeragency.com
 * @version     2.0.1
 * @date        23/03/2026
 */

'use strict';

const WmaEditor = {

    fields: [],               // Array dei campi del form
    selectedFieldIndex: null, // Indice del campo selezionato nelle opzioni
    _settings: {},            // Opzioni di invio del form
    _previewTimer: null,      // Timer per aggiornamento automatico anteprima (debounce)

    _languages: [],           // Lingue attive del sito [{code, title}]
    _baseLang: '',            // Lingua base del form (default sito frontend)
    _activeLang: '__base__',  // Lingua in modifica: '__base__' oppure un lang code
    _translations: {},        // { _base: 'it-IT', '<lang>': { 'field.<uid>.<key>': '...', 'form.title': '...' } }
    _baseTitle: '',           // Titolo del form nella lingua base

    // Definizioni default per ogni tipo di campo
    fieldDefaults: {
        text:        { type: 'text',        label: '', placeholder: '', required: false, readonly: false, hidelabel: false, labelinside: false, width: '100', minchars: 0, maxchars: 0, minwords: 0, maxwords: 0 },
        email:       { type: 'email',       label: '', placeholder: '', required: false, readonly: false, hidelabel: false, labelinside: false, width: '100' },
        textarea:    { type: 'textarea',    label: '', placeholder: '', required: false, readonly: false, hidelabel: false, labelinside: false, width: '100', rows: 6, minchars: 0, maxchars: 0, minwords: 0, maxwords: 0 },
        number:      { type: 'number',      label: '', placeholder: '', required: false, readonly: false, hidelabel: false, labelinside: false, width: '100', min: '', max: '', step: 1 },
        tel:         { type: 'tel',         label: '', placeholder: '', required: false, readonly: false, hidelabel: false, labelinside: false, width: '100', inputmask: '' },
        url:         { type: 'url',         label: '', placeholder: '', required: false, readonly: false, hidelabel: false, labelinside: false, width: '100' },
        dropdown:    { type: 'dropdown',    label: '', required: false, hidelabel: false, width: '100', options: '' },
        radio:       { type: 'radio',       label: '', required: false, hidelabel: false, width: '100', options: '', choicelayout: '1' },
        checkbox:    { type: 'checkbox',    label: '', required: false, hidelabel: false, width: '100', options: '', choicelayout: '1' },
        fileupload:  { type: 'fileupload',  label: '', required: false, hidelabel: false, width: '100', upload_folder: '', max_file_size: 0, upload_types: '' },
        html:        { type: 'html',        label: '', width: '100', content: '' },
        heading:     { type: 'heading',     label: '', width: '100', content: '', level: 'h2', alignment: 'left' },
        divider:     { type: 'divider',     label: '', width: '100', border_style: 'solid', border_width: 1, border_color: '#cccccc' },
        emptyspace:  { type: 'emptyspace',  label: '', width: '100', height: 20 },
        hcaptcha:    { type: 'hcaptcha',    label: '', width: '100', hcaptcha_type: 'checkbox', theme: 'light', size: 'normal' },
        submit:      { type: 'submit',      label: '', width: '100', text: '' },
        privacy:     { type: 'privacy',     label: '', width: '100', privacy_text: '', privacy_url: '' },
        office:      { type: 'office',      label: '', width: '100', required: false, hidelabel: false, options: '' },
    },

    // Traduce una costante Joomla registrata con Text::script()
    t(key) {
        return Joomla.Text._(key) || key;
    },

    // Retrocompatibilità: se un valore salvato e' ancora una costante COM_ nota la traduce,
    // altrimenti lo restituisce invariato (nel modello nuovo le stringhe sono testo semplice).
    tv(value) {
        if (!value) return '';
        value = String(value);
        if (!value.startsWith('COM_')) return value;
        const t = Joomla.Text._(value);
        return t !== value ? t : value;
    },

    init() {
        this.fieldDefaults.office.options    = 'Ufficio 1|ufficio1@esempio.it\nUfficio 2|ufficio2@esempio.it';

        // Opzioni default per campi scelta multipla
        this.fieldDefaults.dropdown.options  = 'Opzione 1\nOpzione 2\nOpzione 3';
        this.fieldDefaults.radio.options     = 'Opzione 1\nOpzione 2\nOpzione 3';
        this.fieldDefaults.checkbox.options  = 'Opzione 1\nOpzione 2\nOpzione 3';

        // Valori default per fileupload
        this.fieldDefaults.fileupload.upload_folder = this.t('COM_WMACOMMUNICATION_FIELD_DEFAULT_UPLOAD_FOLDER');
        this.fieldDefaults.fileupload.upload_types  = this.t('COM_WMACOMMUNICATION_FIELD_DEFAULT_UPLOAD_TYPES');

        // Carica i campi salvati nel DB (JSON nel campo hidden)
        const raw = document.getElementById('wma-input-fields');
        if (raw && raw.value) {
            try { this.fields = JSON.parse(raw.value) || []; } catch(e) { this.fields = []; }
        }

        // Carica le impostazioni di invio salvate nel DB (JSON nel campo hidden)
        const rawSettings = document.getElementById('wma-input-settings');
        if (rawSettings && rawSettings.value) {
            try { this._settings = JSON.parse(rawSettings.value) || {}; } catch(e) { this._settings = {}; }
        }

        // Lingue del sito + lingua base + mappa traduzioni
        const editorEl = document.querySelector('.wma-editor');
        this._baseLang = (editorEl && editorEl.dataset.baseLang) || '';
        try { this._languages = JSON.parse((editorEl && editorEl.dataset.languages) || '[]') || []; } catch(e) { this._languages = []; }
        this._translations = this._settings.translations || {};
        if (!this._translations._base) { this._translations._base = this._baseLang; }
        this._activeLang = '__base__';

        const titleInput = document.getElementById('wma-form-title');
        this._baseTitle = titleInput ? titleInput.value : '';

        // Fix retrocompatibilità per campi salvati con vecchi valori default
        this.fields = this.fields.map(field => {
            if (!field.uid) {
                field.uid = this.genUid();
            }
            if (field.type === 'submit' && !field.text) {
                field.text = this.t('COM_WMACOMMUNICATION_SUBMIT');
            }
            if (['dropdown','radio','checkbox'].includes(field.type) && field.options === 'COM_WMACOMMUNICATION_FIELD_DEFAULT_OPTIONS') {
                field.options = 'Opzione 1\nOpzione 2\nOpzione 3';
            }
            return field;
        });

        this._scrubBaseTranslations();

        this.bindTabs();
        this.bindAddField();
        this.bindLangSelector();
        this.initTitleField();
        this.bindSave();
        this.bindPreview();
        this.renderFieldsList();
        this.renderPreview();
        this.initSendingTab();
        this.initMessagesTab();
        this.updateReplyToOptions();
        this.updateOfficeOptions();
    },

    // -------------------------------------------------------------------------
    // Tabs
    // -------------------------------------------------------------------------

    bindTabs() {
        document.querySelectorAll('.wma-tab-btn').forEach(btn => {
            btn.addEventListener('click', () => this.switchTab(btn.dataset.tab));
        });
    },

    switchTab(tabName) {
        document.querySelectorAll('.wma-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.wma-tab-content').forEach(c => c.classList.remove('active'));

        const btn     = document.querySelector(`.wma-tab-btn[data-tab="${tabName}"]`);
        const content = document.getElementById(`tab-${tabName}`);
        if (btn) btn.classList.add('active');
        if (content) content.classList.add('active');

        const editor = document.querySelector('.wma-editor');
        if (editor) {
            editor.classList.toggle('wma-editor--preview-hidden', tabName === 'sending' || tabName === 'messages');
        }
    },

    // -------------------------------------------------------------------------
    // Multilingua — selettore lingua e gestione stringhe traducibili
    // -------------------------------------------------------------------------

    bindLangSelector() {
        const sel = document.getElementById('wma-editor-lang-select');
        if (!sel) return;
        sel.addEventListener('change', () => this.setActiveLang(sel.value));
    },

    setActiveLang(lang) {
        this._activeLang = lang || '__base__';

        const editor = document.querySelector('.wma-editor');
        if (editor) {
            editor.classList.toggle('wma-editor--translating', !this._isBase());
        }

        this.syncTitleField();
        this.syncMessagesFields();
        if (this.selectedFieldIndex !== null) {
            this.renderOptions(this.selectedFieldIndex);
        }
        this.renderFieldsList();
        this.renderPreview();
    },

    // true quando si sta modificando la lingua base (stringhe scritte in chiaro nel campo)
    _isBase() {
        return this._activeLang === '__base__' || this._activeLang === this._baseLang;
    },

    // Chiavi stringa traducibili per tipo di campo
    _translatableKeys(type) {
        const keys = ['label'];
        if (['text', 'email', 'textarea', 'number', 'tel', 'url'].includes(type)) keys.push('placeholder');
        if (['dropdown', 'radio', 'checkbox'].includes(type)) keys.push('options');
        if (['html', 'heading'].includes(type)) keys.push('content');
        if (type === 'submit') keys.push('text');
        if (type === 'privacy') keys.push('privacy_text', 'privacy_url');
        return keys;
    },

    _transKeyLabel(key) {
        const map = {
            label:        'COM_WMACOMMUNICATION_EDITOR_OPT_LABEL',
            placeholder:  'COM_WMACOMMUNICATION_EDITOR_OPT_PLACEHOLDER',
            options:      'COM_WMACOMMUNICATION_EDITOR_OPT_OPTIONS',
            content:      'COM_WMACOMMUNICATION_EDITOR_OPT_CONTENT',
            text:         'COM_WMACOMMUNICATION_EDITOR_OPT_SUBMIT_TEXT',
            privacy_text: 'COM_WMACOMMUNICATION_EDITOR_OPT_PRIVACY_TEXT',
            privacy_url:  'COM_WMACOMMUNICATION_EDITOR_OPT_PRIVACY_URL',
        };
        return this.t(map[key] || key);
    },

    // Valore di una stringa di campo nella lingua attiva (vuoto se non tradotta)
    fieldStr(field, key) {
        if (this._isBase()) return field[key] ?? '';
        const t = this._translations[this._activeLang] || {};
        const v = t['field.' + field.uid + '.' + key];
        return (v !== undefined && v !== null) ? v : '';
    },

    // Come fieldStr ma con fallback sul valore base (rispecchia la resa lato sito)
    previewStr(field, key) {
        if (this._isBase()) return field[key] ?? '';
        const v = this.fieldStr(field, key);
        return v !== '' ? v : (field[key] ?? '');
    },

    // Scrive una stringa di campo: nel campo se lingua base, in _translations altrimenti
    setFieldStr(index, key, value) {
        const field = this.fields[index];
        if (!field) return;

        if (this._isBase()) {
            field[key] = value;
            return;
        }

        const lang = this._activeLang;
        if (!this._translations[lang]) this._translations[lang] = {};
        const k = 'field.' + field.uid + '.' + key;
        if (value === '') {
            delete this._translations[lang][k];
        } else {
            this._translations[lang][k] = value;
        }
        this.syncTranslations();
    },

    syncTranslations() {
        this._translations._base = this._baseLang;
        this._settings.translations = this._translations;
    },

    // Campo titolo del form nella topbar: lingua base o traduzione
    initTitleField() {
        const titleInput = document.getElementById('wma-form-title');
        if (!titleInput) return;

        titleInput.addEventListener('input', () => {
            if (this._isBase()) {
                this._baseTitle = titleInput.value;
                const hidden = document.getElementById('wma-input-title');
                if (hidden) hidden.value = titleInput.value;
                return;
            }
            const lang = this._activeLang;
            if (!this._translations[lang]) this._translations[lang] = {};
            if (titleInput.value === '') {
                delete this._translations[lang]['form.title'];
            } else {
                this._translations[lang]['form.title'] = titleInput.value;
            }
            this.syncTranslations();
        });
    },

    syncTitleField() {
        const titleInput = document.getElementById('wma-form-title');
        if (!titleInput) return;

        if (this._isBase()) {
            titleInput.value = this._baseTitle;
            titleInput.placeholder = '';
        } else {
            const tr = (this._translations[this._activeLang] || {})['form.title'] || '';
            titleInput.value = tr;
            titleInput.placeholder = this._baseTitle;
        }
    },

    // Pannello opzioni ridotto quando si traduce: solo le stringhe testuali
    renderTranslationOptions(index, field) {
        const langObj  = this._languages.find(l => l.code === this._activeLang) || {};
        const langName = langObj.title || this._activeLang;

        let html = '<div class="wma-field-options-form">';
        html += `<p class="wma-privacy-info">${this.escape(this.t('COM_WMACOMMUNICATION_EDITOR_TRANSLATING_NOTE').replace('%s', langName))}</p>`;

        this._translatableKeys(field.type).forEach(key => {
            const base = field[key] ?? '';
            if (base === '' && key !== 'label') return;

            const cur      = this.fieldStr(field, key);
            const multi    = (key === 'options' || key === 'content');
            const labelTxt = this._transKeyLabel(key);

            if (multi) {
                html += `<div class="wma-field-option">
                    <label>${labelTxt}</label>
                    <textarea data-option="${key}" rows="4" placeholder="${this.escape(String(base))}">${this.escape(String(cur))}</textarea>
                </div>`;
            } else if (key === 'privacy_url') {
                html += `<div class="wma-field-option">
                    <label>${labelTxt}</label>
                    <div class="wma-input-group">
                        <input type="text" data-option="privacy_url" id="wma-opt-privacy-url" value="${this.escape(String(cur))}" placeholder="${this.escape(String(base))}">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="wma-opt-privacy-url-btn"><i class="fa fa-sitemap"></i> ${this.t('COM_WMACOMMUNICATION_EDITOR_OPT_PRIVACY_URL_BUTTON')}</button>
                    </div>
                </div>`;
            } else {
                html += `<div class="wma-field-option">
                    <label>${labelTxt}</label>
                    <input type="text" data-option="${key}" value="${this.escape(String(cur))}" placeholder="${this.escape(String(base))}">
                </div>`;
            }
        });

        html += '</div>';
        return html;
    },

    // Collega gli input del pannello opzioni al modello
    bindOptionInputs(index) {
        const container = document.getElementById('wma-field-options');
        if (!container) return;
        container.querySelectorAll('[data-option]').forEach(el => {
            const fn = () => this.updateFieldOption(index, el.dataset.option, el.type === 'checkbox' ? el.checked : el.value);
            el.addEventListener('change', fn);
            el.addEventListener('input', fn);
        });

        const purlBtn = container.querySelector('#wma-opt-privacy-url-btn');
        if (purlBtn) {
            purlBtn.addEventListener('click', () => {
                const inp = container.querySelector('#wma-opt-privacy-url');
                if (inp) this.openMenuSelector(inp);
            });
        }
    },

    // -------------------------------------------------------------------------
    // Aggiungi campo
    // -------------------------------------------------------------------------

    bindAddField() {
        document.querySelectorAll('.wma-field-type-item').forEach(item => {
            item.addEventListener('click', () => this.addField(item.dataset.type));
        });
    },

    addField(type) {
        const defaults = this.fieldDefaults[type];
        if (!defaults) return;

        const field = Object.assign({}, defaults);
        field.uid = this.genUid();

        // Label predefinita = nome localizzato del tipo di campo, salvato come testo semplice
        if (field.label === '') {
            field.label = this.t('COM_WMACOMMUNICATION_FIELD_' + type.toUpperCase());
        }
        if (type === 'heading' && !field.content) {
            field.content = field.label;
        }
        if (type === 'submit' && !field.text) {
            field.text = this.t('COM_WMACOMMUNICATION_SUBMIT');
        }

        this.fields.push(field);
        this.renderFieldsList();
        this.switchTab('all-fields');
        this.selectField(this.fields.length - 1);
        this.updateReplyToOptions();
        this.updateOfficeOptions();
        this.schedulePreviewUpdate();
    },

    // -------------------------------------------------------------------------
    // Lista campi
    // -------------------------------------------------------------------------

    renderFieldsList() {
        const list = document.getElementById('wma-fields-list');
        if (!list) return;

        list.innerHTML = '';

        if (this.fields.length === 0) {
            list.innerHTML = `<li class="wma-no-selection">${this.t('COM_WMACOMMUNICATION_EDITOR_NO_FIELDS')}</li>`;
            return;
        }

        this.fields.forEach((field, index) => {
            const li = document.createElement('li');
            li.className = 'wma-field-item' + (index === this.selectedFieldIndex ? ' selected' : '');
            li.dataset.index = index;
            li.innerHTML = `
                <span class="wma-field-item-label">${this.escape(this.tv(this.fieldStr(field, 'label')) || field.type)}</span>
                <div class="wma-field-item-actions">
                    <button type="button" class="wma-btn-move-up" data-index="${index}" ${index === 0 ? 'disabled' : ''}>
                        <i class="fa fa-chevron-up"></i>
                    </button>
                    <button type="button" class="wma-btn-move-down" data-index="${index}" ${index === this.fields.length - 1 ? 'disabled' : ''}>
                        <i class="fa fa-chevron-down"></i>
                    </button>
                    <button type="button" class="wma-btn-delete" data-index="${index}">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            `;

            li.addEventListener('click', (e) => {
                if (e.target.closest('.wma-field-item-actions')) return;
                this.selectField(index);
            });

            li.querySelector('.wma-btn-move-up').addEventListener('click', (e) => {
                e.stopPropagation();
                this.moveField(index, -1);
            });

            li.querySelector('.wma-btn-move-down').addEventListener('click', (e) => {
                e.stopPropagation();
                this.moveField(index, 1);
            });

            li.querySelector('.wma-btn-delete').addEventListener('click', (e) => {
                e.stopPropagation();
                this.deleteField(index);
            });

            list.appendChild(li);
        });
    },

    moveField(index, direction) {
        const newIndex = index + direction;
        if (newIndex < 0 || newIndex >= this.fields.length) return;

        [this.fields[index], this.fields[newIndex]] = [this.fields[newIndex], this.fields[index]];

        if (this.selectedFieldIndex === index) {
            this.selectedFieldIndex = newIndex;
        } else if (this.selectedFieldIndex === newIndex) {
            this.selectedFieldIndex = index;
        }

        this.renderFieldsList();
        this.updateReplyToOptions();
        this.updateOfficeOptions();
        this.schedulePreviewUpdate();
    },

    deleteField(index) {
        this.fields.splice(index, 1);

        if (this.selectedFieldIndex === index) {
            this.selectedFieldIndex = null;
            this.renderOptions(null);
        } else if (this.selectedFieldIndex > index) {
            this.selectedFieldIndex--;
        }

        this.renderFieldsList();
        this.updateReplyToOptions();
        this.updateOfficeOptions();
        this.schedulePreviewUpdate();
    },

    // -------------------------------------------------------------------------
    // Selezione campo e opzioni
    // -------------------------------------------------------------------------

    selectField(index) {
        this.selectedFieldIndex = index;
        this.renderFieldsList();
        this.renderOptions(index);
        this.switchTab('options');
    },

    renderOptions(index) {
        const container = document.getElementById('wma-field-options');
        if (!container) return;

        if (index === null || !this.fields[index]) {
            container.innerHTML = `<p class="wma-no-selection">${this.t('COM_WMACOMMUNICATION_EDITOR_SELECT_FIELD')}</p>`;
            return;
        }

        const field = this.fields[index];

        // Modalità traduzione: pannello ridotto alle sole stringhe testuali
        if (!this._isBase()) {
            container.innerHTML = this.renderTranslationOptions(index, field);
            this.bindOptionInputs(index);
            return;
        }

        let html = '<div class="wma-field-options-form">';

        // --- Etichetta ---
        const rawLabel = field.label ?? '';
        const displayLabel = this.tv(rawLabel);
        html += `<div class="wma-field-option">
            <label>${this.t('COM_WMACOMMUNICATION_EDITOR_OPT_LABEL')}</label>
            <input type="text" data-option="label" value="${this.escape(displayLabel)}" data-raw="${this.escape(rawLabel)}">
            <span class="wma-label-hint">${this.t('COM_WMACOMMUNICATION_EDITOR_OPT_LABEL_HINT')}</span>
        </div>`;

        // --- Nascondi etichetta ---
        if (field.type !== 'html') {
            html += this.optionCheck('hidelabel', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_HIDELABEL'), field.hidelabel ?? false);
        }

        // --- Etichetta nel campo ---
        if (['text','email','textarea','number','tel','url'].includes(field.type)) {
            html += this.optionCheck('labelinside', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_LABELINSIDE'), field.labelinside ?? false);
        }

        // --- Placeholder ---
        if (['text','email','textarea','number','tel','url'].includes(field.type)) {
            html += this.optionText('placeholder', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_PLACEHOLDER'), field.placeholder ?? '');
        }

        // --- Obbligatorio (esclusi campi non compilabili e privacy che è sempre required) ---
        if (!['html','heading','divider','emptyspace','hcaptcha','submit','privacy'].includes(field.type)) {
            html += this.optionCheck('required', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_REQUIRED'), field.required ?? false);
        }

        // --- Sola lettura ---
        if (['text','email','textarea','number','tel','url'].includes(field.type)) {
            html += this.optionCheck('readonly', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_READONLY'), field.readonly ?? false);
        }

        // --- Larghezza ---
        html += this.optionSelect('width', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_WIDTH'), field.width ?? '100', {
            '100': this.t('COM_WMACOMMUNICATION_EDITOR_WIDTH_FULL'),
            '75':  this.t('COM_WMACOMMUNICATION_EDITOR_WIDTH_3_4'),
            '66':  this.t('COM_WMACOMMUNICATION_EDITOR_WIDTH_2_3'),
            '50':  this.t('COM_WMACOMMUNICATION_EDITOR_WIDTH_1_2'),
            '33':  this.t('COM_WMACOMMUNICATION_EDITOR_WIDTH_1_3'),
            '25':  this.t('COM_WMACOMMUNICATION_EDITOR_WIDTH_1_4'),
        });

        // --- Opzioni specifiche per tipo ---

        if (field.type === 'text') {
            html += this.optionNumber('minchars', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_MINCHARS'), field.minchars ?? 0, 0);
            html += this.optionNumber('maxchars', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_MAXCHARS'), field.maxchars ?? 0, 0);
            html += this.optionNumber('minwords', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_MINWORDS'), field.minwords ?? 0, 0);
            html += this.optionNumber('maxwords', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_MAXWORDS'), field.maxwords ?? 0, 0);
        }

        if (field.type === 'textarea') {
            html += this.optionNumber('rows', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_ROWS'), field.rows ?? 4, 1);
            html += this.optionNumber('minchars', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_MINCHARS'), field.minchars ?? 0, 0);
            html += this.optionNumber('maxchars', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_MAXCHARS'), field.maxchars ?? 0, 0);
            html += this.optionNumber('minwords', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_MINWORDS'), field.minwords ?? 0, 0);
            html += this.optionNumber('maxwords', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_MAXWORDS'), field.maxwords ?? 0, 0);
        }

        if (field.type === 'number') {
            html += this.optionNumber('min', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_MIN'), field.min ?? '', null);
            html += this.optionNumber('max', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_MAX'), field.max ?? '', null);
            html += this.optionNumber('step', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_STEP'), field.step ?? 1, 1);
        }

        if (field.type === 'tel') {
            html += this.optionText('inputmask', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_INPUTMASK'), field.inputmask ?? '');
        }

        if (field.type === 'dropdown') {
            html += this.optionTextarea('options', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_OPTIONS'), field.options ?? '');
        }

        if (['radio','checkbox'].includes(field.type)) {
            html += this.optionTextarea('options', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_OPTIONS'), field.options ?? '');
            html += this.optionSelect('choicelayout', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_LAYOUT'), field.choicelayout ?? '1', {
                '1':    this.t('COM_WMACOMMUNICATION_EDITOR_LAYOUT_1COL'),
                '2':    this.t('COM_WMACOMMUNICATION_EDITOR_LAYOUT_2COL'),
                '3':    this.t('COM_WMACOMMUNICATION_EDITOR_LAYOUT_3COL'),
                'auto': this.t('COM_WMACOMMUNICATION_EDITOR_LAYOUT_SIDE'),
            });
        }

        // --- Ufficio: formato Nome|email, una per riga ---
        if (field.type === 'office') {
            html += this.optionTextarea('options', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_OFFICE_OPTIONS'), field.options ?? '');
            html += `<div class="wma-field-option">
                <p class="wma-privacy-info">${this.t('COM_WMACOMMUNICATION_EDITOR_OFFICE_INFO')}</p>
            </div>`;
        }

        if (field.type === 'fileupload') {
            html += `<div class="wma-field-option">
                <p class="wma-privacy-info">${this.t('COM_WMACOMMUNICATION_EDITOR_UPLOAD_INFO')}</p>
            </div>`;
            html += this.optionText('upload_folder', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_UPLOAD_FOLDER'), field.upload_folder ?? '');
            html += '    <small class="wma-msg-hint">' + this.t('COM_WMACOMMUNICATION_EDITOR_OPT_UPLOAD_FOLDER_HINT') + '</small>';
            html += this.optionNumber('max_file_size', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_MAX_FILE_SIZE'), field.max_file_size ?? 0, 0);
            html += '    <small class="wma-msg-hint">' + this.t('COM_WMACOMMUNICATION_EDITOR_OPT_MAX_FILE_SIZE_HINT') + '</small>';
            html += this.optionText('upload_types', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_UPLOAD_TYPES'), field.upload_types ?? '');
            html += '    <small class="wma-msg-hint">' + this.t('COM_WMACOMMUNICATION_EDITOR_OPT_UPLOAD_TYPES_HINT') + '</small>';
        }

        if (field.type === 'html') {
            html += this.optionTextarea('content', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_CONTENT'), field.content ?? '');
        }

        if (field.type === 'heading') {
            html += this.optionText('content', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_CONTENT'), field.content ?? '');
            html += this.optionSelect('level', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_LEVEL'), field.level ?? 'h2', {
                h1: 'H1', h2: 'H2', h3: 'H3', h4: 'H4', h5: 'H5', h6: 'H6'
            });
            html += this.optionSelect('alignment', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_ALIGNMENT'), field.alignment ?? 'left', {
                left:   this.t('COM_WMACOMMUNICATION_EDITOR_ALIGN_LEFT'),
                center: this.t('COM_WMACOMMUNICATION_EDITOR_ALIGN_CENTER'),
                right:  this.t('COM_WMACOMMUNICATION_EDITOR_ALIGN_RIGHT'),
            });
        }

        if (field.type === 'divider') {
            html += this.optionSelect('border_style', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_BORDER_STYLE'), field.border_style ?? 'solid', {
                solid:  this.t('COM_WMACOMMUNICATION_EDITOR_BORDER_SOLID'),
                dashed: this.t('COM_WMACOMMUNICATION_EDITOR_BORDER_DASHED'),
                dotted: this.t('COM_WMACOMMUNICATION_EDITOR_BORDER_DOTTED'),
            });
            html += this.optionNumber('border_width', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_BORDER_WIDTH'), field.border_width ?? 1, 1);
            html += this.optionColor('border_color', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_BORDER_COLOR'), field.border_color ?? '#cccccc');
        }

        if (field.type === 'emptyspace') {
            html += this.optionNumber('height', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_HEIGHT'), field.height ?? 20, 0);
        }

        if (field.type === 'hcaptcha') {
            html += this.optionSelect('hcaptcha_type', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_HCAPTCHA_TYPE'), field.hcaptcha_type ?? 'checkbox', {
                checkbox:  this.t('COM_WMACOMMUNICATION_EDITOR_HCAPTCHA_CHECKBOX'),
                invisible: this.t('COM_WMACOMMUNICATION_EDITOR_HCAPTCHA_INVISIBLE'),
            });
            html += this.optionSelect('theme', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_THEME'), field.theme ?? 'light', {
                light: this.t('COM_WMACOMMUNICATION_EDITOR_THEME_LIGHT'),
                dark:  this.t('COM_WMACOMMUNICATION_EDITOR_THEME_DARK'),
            });
            html += this.optionSelect('size', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_SIZE'), field.size ?? 'normal', {
                normal:  this.t('COM_WMACOMMUNICATION_EDITOR_SIZE_NORMAL'),
                compact: this.t('COM_WMACOMMUNICATION_EDITOR_SIZE_COMPACT'),
            });
        }

        // --- Privacy: testo del consenso + URL della pagina privacy ---
        if (field.type === 'privacy') {
            html += this.optionText('privacy_text', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_PRIVACY_TEXT'), field.privacy_text ?? '');
            html += `<div class="wma-field-option">
                <label>${this.t('COM_WMACOMMUNICATION_EDITOR_OPT_PRIVACY_URL')}</label>
                <div class="wma-input-group">
                    <input type="text" data-option="privacy_url" id="wma-opt-privacy-url" value="${this.escape(field.privacy_url ?? '')}" placeholder="index.php?Itemid=123">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="wma-opt-privacy-url-btn"><i class="fa fa-sitemap"></i> ${this.t('COM_WMACOMMUNICATION_EDITOR_OPT_PRIVACY_URL_BUTTON')}</button>
                </div>
                <small class="wma-msg-hint">${this.t('COM_WMACOMMUNICATION_EDITOR_OPT_PRIVACY_URL_HINT')}</small>
            </div>`;
        }

        if (field.type === 'submit') {
            html += this.optionText('text', this.t('COM_WMACOMMUNICATION_EDITOR_OPT_SUBMIT_TEXT'), field.text ?? '');
        }

        html += '</div>';
        container.innerHTML = html;

        this.bindOptionInputs(index);
    },

    updateFieldOption(index, key, value) {
        if (!this.fields[index]) return;

        // Modalità traduzione: le chiavi testuali vanno in _translations
        if (!this._isBase() && this._translatableKeys(this.fields[index].type).includes(key)) {
            this.setFieldStr(index, key, value);
            if (key === 'label') {
                const item = document.querySelector(`.wma-field-item[data-index="${index}"] .wma-field-item-label`);
                if (item) item.textContent = this.tv(this.fieldStr(this.fields[index], 'label')) || this.fields[index].type;
            }
            this.schedulePreviewUpdate();
            return;
        }

        // Per la label: se l'input mostra il testo tradotto ma il raw era una costante COM_,
        // preserva la costante originale finché l'utente non modifica manualmente il testo
        if (key === 'label') {
            const input = document.querySelector(`[data-option="label"]`);
            const raw = input?.dataset?.raw || '';
            if (raw !== value && value === this.tv(raw) && raw.startsWith('COM_')) {
                value = raw;
            } else {
                input.dataset.raw = value;
            }
        }

        this.fields[index][key] = value;

        // Aggiorna la label nella lista campi
        if (key === 'label') {
            const item = document.querySelector(`.wma-field-item[data-index="${index}"] .wma-field-item-label`);
            if (item) item.textContent = this.tv(value) || this.fields[index].type;
        }

        // Se cambiano le opzioni di un campo office, aggiorna la select nella tab Invio
        if (this.fields[index].type === 'office' && key === 'options') {
            this.updateOfficeOptions();
        }

        this.schedulePreviewUpdate();
    },

    // -------------------------------------------------------------------------
    // Helper HTML opzioni campo
    // -------------------------------------------------------------------------

    optionText(key, label, value) {
        return `<div class="wma-field-option">
            <label>${label}</label>
            <input type="text" data-option="${key}" value="${this.escape(String(value))}">
        </div>`;
    },

    optionNumber(key, label, value, min) {
        const minAttr = min !== null ? `min="${min}"` : '';
        return `<div class="wma-field-option">
            <label>${label}</label>
            <input type="number" data-option="${key}" value="${this.escape(String(value))}" ${minAttr}>
        </div>`;
    },

    optionTextarea(key, label, value) {
        return `<div class="wma-field-option">
            <label>${label}</label>
            <textarea data-option="${key}" rows="5">${this.escape(String(value))}</textarea>
        </div>`;
    },

    optionCheck(key, label, value) {
        return `<div class="wma-field-option wma-field-option-check">
            <input type="checkbox" data-option="${key}" id="wma-opt-${key}" ${value ? 'checked' : ''}>
            <label for="wma-opt-${key}">${label}</label>
        </div>`;
    },

    optionSelect(key, label, value, options) {
        const opts = Object.entries(options)
            .map(([v, t]) => `<option value="${v}" ${v === String(value) ? 'selected' : ''}>${t}</option>`)
            .join('');
        return `<div class="wma-field-option">
            <label>${label}</label>
            <select data-option="${key}">${opts}</select>
        </div>`;
    },

    optionColor(key, label, value) {
        return `<div class="wma-field-option wma-field-option-check">
            <label>${label}</label>
            <input type="color" data-option="${key}" value="${this.escape(String(value))}">
        </div>`;
    },

    // -------------------------------------------------------------------------
    // Anteprima
    // -------------------------------------------------------------------------

    bindPreview() {
        const btn = document.getElementById('wma-refresh-preview');
        if (btn) btn.addEventListener('click', () => this.renderPreview());
    },

    // Aggiornamento automatico anteprima con debounce 500ms
    // Evita chiamate AJAX eccessive durante la digitazione
    schedulePreviewUpdate() {
        if (this._previewTimer) clearTimeout(this._previewTimer);
        this._previewTimer = setTimeout(() => this.renderPreview(), 500);
    },

    // -------------------------------------------------------------------------
    // Tab Invio
    // -------------------------------------------------------------------------

    initSendingTab() {
        // Collega i campi della tab Invio alle impostazioni _settings
        const fields = ['to', 'cc', 'ccn', 'sender-name'];
        fields.forEach(key => {
            const el = document.getElementById(`wma-sending-${key}`);
            if (!el) return;
            const settingKey = key.replaceAll('-', '_');
            if (this._settings[settingKey]) el.value = this._settings[settingKey];
            el.addEventListener('input', () => { this._settings[settingKey] = el.value; });
            el.addEventListener('change', () => { this._settings[settingKey] = el.value; });
        });

        // Select copia per lo scrivente (campo email del form)
        const replyTo = document.getElementById('wma-sending-replyto');
        if (replyTo) {
            if (this._settings.replyto) replyTo.value = this._settings.replyto;
            replyTo.addEventListener('change', () => { this._settings.replyto = replyTo.value; });
        }

        // Select destinatario ufficio (campo office del form)
        const officeTo = document.getElementById('wma-sending-officeto');
        if (officeTo) {
            if (this._settings.officeto) officeTo.value = this._settings.officeto;
            officeTo.addEventListener('change', () => { this._settings.officeto = officeTo.value; });
        }
    },

    // Popola la select "Copia per lo scrivente" con i campi email presenti nel form
    updateReplyToOptions() {
        const select = document.getElementById('wma-sending-replyto');
        if (!select) return;

        const currentVal = this._settings.replyto ?? '';
        while (select.options.length > 1) select.remove(1);

        this.fields.forEach((field, index) => {
            if (field.type === 'email') {
                const opt = document.createElement('option');
                opt.value = index;
                opt.textContent = this.tv(field.label) || `Email ${index + 1}`;
                if (String(currentVal) === String(index)) opt.selected = true;
                select.appendChild(opt);
            }
        });
    },

    // Popola la select "Destinatario da campo ufficio" con i campi office presenti nel form
    updateOfficeOptions() {
        const select = document.getElementById('wma-sending-officeto');
        if (!select) return;

        const currentVal = this._settings.officeto ?? '';
        while (select.options.length > 1) select.remove(1);

        this.fields.forEach((field, index) => {
            if (field.type === 'office') {
                const opt = document.createElement('option');
                opt.value = index;
                opt.textContent = this.tv(field.label) || `Ufficio ${index + 1}`;
                if (String(currentVal) === String(index)) opt.selected = true;
                select.appendChild(opt);
            }
        });
    },

    // -------------------------------------------------------------------------
    // Messaggi — un blocco unico, segue il selettore di lingua dell'editor
    // -------------------------------------------------------------------------

    _msgFieldMap: {
        'wma-msg-success':    'success_msg',
        'wma-msg-email-body': 'email_body',
    },

    initMessagesTab() {
        const block = document.getElementById('wma-messages-block');
        if (!block) return;

        this._migrateMessages();
        this._migratePrivacyToField();

        if (!this._settings.messages_base) this._settings.messages_base = {};

        Object.entries(this._msgFieldMap).forEach(([id, key]) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.dataset.ph0 = el.getAttribute('placeholder') || '';
            el.addEventListener('input', () => this.setMsgStr(key, el.value));
        });

        const linkBtn = document.getElementById('wma-msg-btn-link');
        if (linkBtn) linkBtn.addEventListener('click', () => this._msgInsert('link'));

        const imageBtn = document.getElementById('wma-msg-btn-image');
        if (imageBtn) imageBtn.addEventListener('click', () => this._msgInsert('image'));

        this.syncMessagesFields();
    },

    // Migra il vecchio _settings.messages { all|lang: {...} } al nuovo modello
    _migrateMessages() {
        const old = this._settings.messages;
        if (!old || this._settings.messages_base) return;

        const base = old._base || old.all || {};
        this._settings.messages_base = {
            success_msg: base.success_msg || '',
            email_body:  base.email_body  || '',
        };

        // Vecchio privacy_text/url dei messaggi → sul campo privacy (vedi _migratePrivacyToField)
        this._legacyPrivacy = {
            base: { text: base.privacy_text || '', url: base.privacy_url || '' },
            byLang: {},
        };

        Object.keys(old).forEach(lang => {
            if (lang === '_base' || lang === 'all') return;
            const r = old[lang] || {};

            // La lingua base non ha traduzioni: i suoi valori stanno inline
            if (lang === this._baseLang) {
                if (r.success_msg && !this._settings.messages_base.success_msg) this._settings.messages_base.success_msg = r.success_msg;
                if (r.email_body && !this._settings.messages_base.email_body) this._settings.messages_base.email_body = r.email_body;
                if (r.privacy_text && !this._legacyPrivacy.base.text) this._legacyPrivacy.base.text = r.privacy_text;
                if (r.privacy_url && !this._legacyPrivacy.base.url) this._legacyPrivacy.base.url = r.privacy_url;
                return;
            }

            if (!this._translations[lang]) this._translations[lang] = {};
            ['success_msg', 'email_body'].forEach(k => {
                if (r[k]) this._translations[lang]['msg.' + k] = r[k];
            });
            if (r.privacy_text || r.privacy_url) {
                this._legacyPrivacy.byLang[lang] = { text: r.privacy_text || '', url: r.privacy_url || '' };
            }
        });

        delete this._settings.messages;
        this.syncTranslations();
    },

    // Sposta privacy_text/url legacy (vecchi messaggi + messages_base + msg.* tradotti)
    // sul primo campo privacy del form. Idempotente: si puo' chiamare sempre.
    _migratePrivacyToField() {
        const field = this.fields.find(f => f.type === 'privacy');

        // 1) Da _settings.messages vecchio (raccolto in _legacyPrivacy da _migrateMessages)
        const legacy = this._legacyPrivacy;
        if (legacy && field) {
            if (!field.privacy_text && legacy.base.text) field.privacy_text = legacy.base.text;
            if (!field.privacy_url  && legacy.base.url)  field.privacy_url  = legacy.base.url;
            Object.keys(legacy.byLang).forEach(lang => {
                if (!this._translations[lang]) this._translations[lang] = {};
                const t = this._translations[lang];
                if (legacy.byLang[lang].text && !t['field.' + field.uid + '.privacy_text']) {
                    t['field.' + field.uid + '.privacy_text'] = legacy.byLang[lang].text;
                }
                if (legacy.byLang[lang].url && !t['field.' + field.uid + '.privacy_url']) {
                    t['field.' + field.uid + '.privacy_url'] = legacy.byLang[lang].url;
                }
            });
        }
        this._legacyPrivacy = null;

        // 2) Da _settings.messages_base (form gia' migrati alla fase precedente)
        const mb = this._settings.messages_base || {};
        if (mb.privacy_text || mb.privacy_url) {
            if (field) {
                if (!field.privacy_text && mb.privacy_text) field.privacy_text = mb.privacy_text;
                if (!field.privacy_url  && mb.privacy_url)  field.privacy_url  = mb.privacy_url;
            }
            delete mb.privacy_text;
            delete mb.privacy_url;
        }

        // 3) Da _translations[lang]['msg.privacy_*'] → field.<uid>.privacy_* (o campo base)
        Object.keys(this._translations).forEach(lang => {
            if (lang === '_base') return;
            const t = this._translations[lang] || {};
            const isBaseLang = (lang === this._baseLang);
            ['privacy_text', 'privacy_url'].forEach(k => {
                if (t['msg.' + k]) {
                    if (field) {
                        if (isBaseLang) {
                            if (!field[k]) field[k] = t['msg.' + k];
                        } else if (!t['field.' + field.uid + '.' + k]) {
                            t['field.' + field.uid + '.' + k] = t['msg.' + k];
                        }
                    }
                    delete t['msg.' + k];
                }
            });
        });

        this._scrubBaseTranslations();
        this.syncTranslations();
    },

    // La lingua base NON deve avere una voce in _translations: i suoi valori sono inline.
    // Voci base finite qui per bug di migrazioni precedenti vengono riportate su campo/titolo/
    // messaggi base e la chiave della lingua base viene rimossa da _translations.
    _scrubBaseTranslations() {
        const base = this._baseLang;
        if (!base || !this._translations[base]) return;

        const t = this._translations[base];
        Object.keys(t).forEach(k => {
            const m = k.match(/^field\.([^.]+)\.(.+)$/);
            if (m) {
                const f = this.fields.find(fl => fl.uid === m[1]);
                if (f && (f[m[2]] === undefined || f[m[2]] === '')) f[m[2]] = t[k];
            } else if (k === 'form.title') {
                if (!this._baseTitle) this._baseTitle = t[k];
            } else if (k.indexOf('msg.') === 0) {
                const mk = k.slice(4);
                if (!this._settings.messages_base) this._settings.messages_base = {};
                if (!this._settings.messages_base[mk]) this._settings.messages_base[mk] = t[k];
            }
        });

        delete this._translations[base];
    },

    // Valore di un messaggio nella lingua attiva (vuoto se non tradotto)
    msgStr(key) {
        if (this._isBase()) return (this._settings.messages_base || {})[key] || '';
        const t = this._translations[this._activeLang] || {};
        const v = t['msg.' + key];
        return (v !== undefined && v !== null) ? v : '';
    },

    setMsgStr(key, value) {
        if (this._isBase()) {
            if (!this._settings.messages_base) this._settings.messages_base = {};
            this._settings.messages_base[key] = value;
            return;
        }
        const lang = this._activeLang;
        if (!this._translations[lang]) this._translations[lang] = {};
        const k = 'msg.' + key;
        if (value === '') delete this._translations[lang][k];
        else this._translations[lang][k] = value;
        this.syncTranslations();
    },

    // Riempie gli input dei messaggi con i valori della lingua attiva
    syncMessagesFields() {
        const base = this._settings.messages_base || {};
        Object.entries(this._msgFieldMap).forEach(([id, key]) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.value = this.msgStr(key);
            el.placeholder = this._isBase() ? (el.dataset.ph0 || '') : (base[key] || '');
        });
    },

    _msgInsert(kind) {
        const ta = document.getElementById('wma-msg-email-body');
        if (!ta) return;

        if (kind === 'link') {
            const url   = prompt(this.t('COM_WMACOMMUNICATION_MESSAGES_LINK_PROMPT_URL'), 'https://');
            const text  = prompt(this.t('COM_WMACOMMUNICATION_MESSAGES_LINK_PROMPT_TEXT'), '');
            const title = prompt(this.t('COM_WMACOMMUNICATION_MESSAGES_LINK_PROMPT_TITLE'), '');
            if (url && text) {
                const titleAttr = title ? ' title="' + this.escape(title) + '"' : '';
                this.insertAtCursor(ta, '<a href="' + url + '"' + titleAttr + ' rel="noopener noreferrer">' + text + '</a>');
            }
        } else {
            const url   = prompt(this.t('COM_WMACOMMUNICATION_MESSAGES_LOGO_PROMPT_URL'), 'https://website.ext/images/logo/logo.png');
            const alt   = prompt(this.t('COM_WMACOMMUNICATION_MESSAGES_LOGO_PROMPT_ALT'), '');
            const width = prompt(this.t('COM_WMACOMMUNICATION_MESSAGES_LOGO_PROMPT_WIDTH'), '120');
            if (url) {
                const widthAttr = width ? ' width="' + parseInt(width) + '"' : '';
                this.insertAtCursor(ta, '<img src="' + url + '" alt="' + this.escape(alt) + '"' + widthAttr + ' style="display:block;border:0;outline:none">');
            }
        }
    },

    openMenuSelector(inputEl) {
        if (!inputEl) return;

        var handler = function(event) {
            var data = event.data || {};
            if (data.messageType !== 'joomla:content-select') return;
            var link = data.uri || '';
            if (link) {
                // Aggancia la lingua della voce di menu (il core lo fa in items/modal.php):
                // senza &lang il router in multilingua puo' risolvere l'Itemid sbagliato
                var lang = data.language || '';
                if (lang && lang !== '*' && link.indexOf('lang=') === -1) {
                    link += (link.indexOf('?') === -1 ? '?' : '&') + 'lang=' + encodeURIComponent(lang);
                }
                inputEl.value = link;
                inputEl.dispatchEvent(new Event('input'));
            }
            window.removeEventListener('message', handler);
            var c = document.getElementById('wma-menu-modal');
            if (c) c.remove();
        };
        window.addEventListener('message', handler);

        var existing = document.getElementById('wma-menu-modal');
        if (existing) existing.remove();

        var url = 'index.php?option=com_menus&view=items&layout=modal&tmpl=component';
        var c = document.createElement('div');
        c.id = 'wma-menu-modal';
        c.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;z-index:10000;display:flex;align-items:center;justify-content:center;';
        var o = document.createElement('div');
        o.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);';
        o.addEventListener('click', function() { c.remove(); window.removeEventListener('message', handler); });
        var f = document.createElement('div');
        f.style.cssText = 'position:relative;width:80%;max-width:900px;height:75%;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 5px 30px rgba(0,0,0,0.3);';
        var b = document.createElement('button');
        b.innerHTML = '&times;';
        b.style.cssText = 'position:absolute;top:6px;right:10px;z-index:1;background:none;border:none;font-size:26px;cursor:pointer;color:#666;line-height:1;';
        b.setAttribute('aria-label', 'Close');
        b.addEventListener('click', function() { c.remove(); window.removeEventListener('message', handler); });
        var i = document.createElement('iframe');
        i.src = url;
        i.style.cssText = 'width:100%;height:100%;border:0;display:block;';
        f.appendChild(b);
        f.appendChild(i);
        c.appendChild(o);
        c.appendChild(f);
        document.body.appendChild(c);
    },

    renderPreview() {
        const container = document.getElementById('wma-preview-container');
        if (!container) return;

        if (this.fields.length === 0) {
            container.innerHTML = `<p class="wma-preview-empty">${this.t('COM_WMACOMMUNICATION_EDITOR_PREVIEW_EMPTY')}</p>`;
            return;
        }

        let html = '<div class="wma-preview-form"><div class="wma-preview-fields">';
        this.fields.forEach(field => {
            html += this.renderPreviewField(field);
        });
        html += '</div></div>';
        container.innerHTML = html;
    },

    renderPreviewField(field) {
        const width    = field.width ?? '100';
        const req      = field.required ? ' <span class="wma-required">*</span>' : '';
        const labelVal = this.tv(this.previewStr(field, 'label'));
        const label    = field.labelinside ? '' : (field.hidelabel ? '' : `<label>${this.escape(labelVal)}${req}</label>`);
        const pholder  = field.labelinside ? this.escape(labelVal) : this.escape(this.tv(this.previewStr(field, 'placeholder')));
        const readonly = field.readonly ? 'readonly' : '';

        const widthMap = { '25': '25%', '33': 'calc(100% / 3)', '50': '50%', '66': 'calc(200% / 3)', '75': '75%', '100': '100%' };
        const widthCss = widthMap[width] || (width + '%');
        const wrap = (inner) => `<div class="wma-preview-field" style="width:${widthCss}">${inner}</div>`;

        switch (field.type) {
            case 'text':
            case 'email':
            case 'tel':
            case 'url':
                return wrap(`${label}<input type="${field.type}" placeholder="${pholder}" ${readonly}>`);

            case 'number':
                return wrap(`${label}<input type="number" placeholder="${pholder}" ${readonly}>`);

            case 'textarea':
                return wrap(`${label}<textarea rows="${field.rows ?? 4}" placeholder="${pholder}" ${readonly}></textarea>`);

            case 'dropdown': {
                const opts = this.previewStr(field, 'options').split('\n').filter(o => o.trim())
                    .map(o => `<option>${this.escape(this.tv(o.trim()))}</option>`).join('');
                const lbl = field.hidelabel ? '' : `<label>${this.escape(labelVal)}${req}</label>`;
                return wrap(`${lbl}<select><option value="">${this.t('COM_WMACOMMUNICATION_SELECT_PLACEHOLDER')}</option>${opts}</select>`);
            }

            // Campo ufficio: mostra solo il nome (la parte prima del |)
            case 'office': {
                const opts = (field.options ?? '').split('\n').filter(o => o.trim())
                    .map(o => {
                        const parts = o.split('|');
                        const name  = this.escape(parts[0].trim());
                        return `<option>${name}</option>`;
                    }).join('');
                const lbl = field.hidelabel ? '' : `<label>${this.escape(labelVal)}${req}</label>`;
                return wrap(`${lbl}<select><option value="">${this.t('COM_WMACOMMUNICATION_SELECT_PLACEHOLDER')}</option>${opts}</select>`);
            }

            case 'radio': {
                const opts = this.previewStr(field, 'options').split('\n').filter(o => o.trim())
                    .map(o => `<div><label><input type="radio"> ${this.escape(this.tv(o.trim()))}</label></div>`).join('');
                const lbl = field.hidelabel ? '' : `<label>${this.escape(labelVal)}${req}</label>`;
                return wrap(`${lbl}${opts}`);
            }

            case 'checkbox': {
                const opts = this.previewStr(field, 'options').split('\n').filter(o => o.trim())
                    .map(o => `<div><label><input type="checkbox"> ${this.escape(this.tv(o.trim()))}</label></div>`).join('');
                const lbl = field.hidelabel ? '' : `<label>${this.escape(labelVal)}${req}</label>`;
                return wrap(`${lbl}${opts}`);
            }

            case 'fileupload': {
                const lbl = field.hidelabel ? '' : `<label>${this.escape(labelVal)}${req}</label>`;
                return wrap(`${lbl}<input type="file">`);
            }

            case 'html':
                return wrap(`${this.tv(this.previewStr(field, 'content')) ?? ''}`);

            case 'heading': {
                const tag   = field.level ?? 'h2';
                const align = field.alignment ?? 'left';
                return wrap(`<${tag} style="text-align:${align}">${this.escape(this.tv(this.previewStr(field, 'content')) ?? '')}</${tag}>`);
            }

            case 'divider': {
                const style = `border-top: ${field.border_width ?? 1}px ${field.border_style ?? 'solid'} ${field.border_color ?? '#cccccc'};`;
                return wrap(`<hr style="${style}">`);
            }

            case 'emptyspace':
                return `<div style="width:${width}%;height:${parseInt(field.height) || 20}px"></div>`;

            case 'hcaptcha':
                return wrap(`<div class="wma-preview-hcaptcha">Non sono un robot (hCaptcha)</div>`);

            case 'submit': {
                const submitText = this.tv(this.previewStr(field, 'text') || 'COM_WMACOMMUNICATION_SUBMIT');
                return wrap(`<button type="button" class="btn btn-primary">${this.escape(submitText)}</button>`);
            }

            case 'privacy': {
                let txt = this.previewStr(field, 'privacy_text');
                if (!txt) txt = this.t('COM_WMACOMMUNICATION_FIELD_PRIVACY_DEFAULT');
                return wrap('<div class="wma-preview-privacy"><label><input type="checkbox"> <span>' + this.escape(txt) + '</span></label></div>');
            }

            default:
                return '';
        }
    },

    // -------------------------------------------------------------------------
    // Salvataggio
    // -------------------------------------------------------------------------

        bindSave() {
            const form = document.getElementById('adminForm');
            if (!form) return;

            form.addEventListener('submit', (e) => {
                const taskEl = form.querySelector('input[name=task]');
                const task   = taskEl ? taskEl.value : '';

                // Non validare il titolo se è un'azione di uscita (cancel/close)
                if (task === 'form.cancel' || task === 'form.close') {
                    return;
                }

                const titleInput = document.getElementById('wma-form-title');

                // Il titolo obbligatorio è quello della lingua base
                if (!this._baseTitle || !this._baseTitle.trim()) {
                    e.preventDefault();
                    if (titleInput) {
                        this.setActiveLang('__base__');
                        const sel = document.getElementById('wma-editor-lang-select');
                        if (sel) sel.value = '__base__';
                        titleInput.style.borderColor = 'red';
                        titleInput.focus();
                    }
                    alert(this.t('COM_WMACOMMUNICATION_FORM_TITLE_REQUIRED'));
                    return;
                }

                if (titleInput) titleInput.style.borderColor = '';

                this.syncTranslations();

                document.getElementById('wma-input-title').value   = this._baseTitle;
                document.getElementById('wma-input-fields').value  = JSON.stringify(this.fields);

                const inputSettings = document.getElementById('wma-input-settings');
                if (inputSettings) inputSettings.value = JSON.stringify(this._settings);
            });
        },

    // -------------------------------------------------------------------------
    // Utility
    // -------------------------------------------------------------------------

    // Genera un identificatore stabile per un campo (f_ + 8 hex)
    genUid() {
        if (window.crypto && crypto.getRandomValues) {
            const b = new Uint8Array(4);
            crypto.getRandomValues(b);
            return 'f_' + Array.from(b, x => x.toString(16).padStart(2, '0')).join('');
        }
        return 'f_' + Math.random().toString(16).slice(2, 10).padEnd(8, '0');
    },

    // Escape HTML per output sicuro nei template
    escape(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    },

    // Inserisce testo in un textarea alla posizione del cursore
    insertAtCursor(textarea, text) {
        if (!textarea || !text) return;
        var start = textarea.selectionStart;
        var end   = textarea.selectionEnd;
        var before = textarea.value.substring(0, start);
        var after  = textarea.value.substring(end);
        textarea.value = before + text + after;
        textarea.selectionStart = textarea.selectionEnd = start + text.length;
        textarea.focus();
        textarea.dispatchEvent(new Event('input'));
    }
};

document.addEventListener('DOMContentLoaded', () => WmaEditor.init());