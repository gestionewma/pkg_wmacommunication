# WMA Communication — Joomla 6 package

*[Italiano più sotto ↓](#wma-communication--pacchetto-joomla-6)*

Advanced form builder for Joomla 6: **component** `com_wmacommunication` +
**module** `mod_wmacommunication` + **plugin** `plg_content_wmacommunication`,
distributed as a single package `pkg_wmacommunication`.

Lets you build complex contact forms visually (drag & drop, no code),
multilingual, with fully customizable email delivery and optional storage of
submissions in the database.

## Features

### Visual form editor

- 20 field types: text, email, phone, URL, number, textarea, dropdown, radio,
  checkbox, file upload, free HTML, heading, divider, spacer, hCaptcha,
  privacy, office (recipient chosen by the visitor), automatic fields "page
  URL"/"article title", submit button.
- Drag & drop field reordering, real-time form preview.
- Per-field options: required, width (100/75/66/50/33/25%), hidden or
  inline label, placeholder, character/word limits, read-only, plus
  type-specific options (numeric min/max, dropdown/radio/checkbox options,
  office format `Name|email`, etc.).
- Sample forms installable with one click from the dashboard, ready to
  customize.

### Native multilingual support

- Translations are managed **inside the form**: no language constants, no
  Joomla language overrides to configure separately.
- Language selector in the editor: fill in the base language normally, then
  switch to other languages and translate what's needed — a field left empty
  automatically falls back to the base-language text.
- On single-language sites the selector disappears and you just type the
  text.

### Email delivery

- Configurable recipients (To, Cc, Bcc), automatic "Reply-To" from the
  form's email field, delivery to an office/department chosen by the
  visitor via the "office" field, customizable sender name.
- Fully customizable email body and success message, with `{Label}`
  placeholders (case-insensitive) replaced with the visitor's submitted
  values — ready-made buttons in the editor to insert them with one click.
- Link and Image buttons to insert safe HTML markup into the email body.
- Rendered preview of the email body before saving.
- **Message template library**: HTML templates shared across all forms
  (with some ready-made ones included), loadable into the email body with
  one click, exportable/importable individually.

### Saving submissions to the database (optional, per form)

- Each form can choose its delivery mode: email only (default), email +
  save to database, or database only (no email).
- Dedicated admin section **"Submissions"**: list filterable by form and by
  read/unread status, text search, full detail view of each submission
  (with links to any attachments), CSV export, unread-submissions counter on
  the dashboard.
- Configurable list columns: on text/email fields you can choose whether
  and in which column (1 or 2) to show the value when the list is filtered
  to a specific form.
- Configurable retention in days, same as for attachments (0 = never
  delete).

### Attachments

- File uploads stored **privately, outside the web root** (when the hosting
  allows it, otherwise a protected fallback with `.htaccess`/`web.config`),
  never directly reachable via URL.
- Download only through a link with a unique token, no login required.
- Security filters: always-on denylist of dangerous extensions,
  configurable allowlist, real file content check, size limit.
- Configurable retention (days), 0 = never delete.

### Automatic fields

- **Page URL**: the full address of the page the form is on, filled in by
  Joomla automatically.
- **Article title**: the title of the article the form is placed in (works
  from a direct menu item, from a module in a position on an article page,
  or from `{loadposition}` in the article text — requires the plugin
  included in the package).
- Shown read-only on the form by default (can be turned off per field), the
  value is always included in emails/saved submissions regardless.

### Security

- Anti-spam protection via hCaptcha (key configurable in the component
  options).
- Privacy field with a required checkbox, text and URL customizable and
  translatable per language.
- Field validation both server-side and client-side (email, URL, phone,
  number with min/max, allowed choices).
- CSRF token on every action, ACL checks on admin tasks, upload hardened
  against path traversal and executable files.

### Module `mod_wmacommunication`

Publishes a form as a module in any template position, using the same
rendering engine as the component (multilingual included).

### Plugin `plg_content_wmacommunication`

Captures the current article's title to feed the "article title" automatic
field; activates itself, no configuration required.

### Automatic updates

Once the package is installed, new versions are detected from **System →
Update** and also show a detailed changelog (clickable badge in "Manage
Extensions") for the component, module, plugin and package.

## Repo structure

```
com_wmacommunication/          component source (wmacommunication.xml at the root)
mod_wmacommunication/          module source
plg_content_wmacommunication/  content plugin (captures article title for automatic fields)
pkg_wmacommunication/          package manifest + script + languages
pkg_wmacommunication_update.xml   update server (served via raw.githubusercontent)
changelog.xml                     native Joomla changelog (badge in Manage Extensions)
.github/workflows/release.yml     automatic ZIP build on every release
```

## Requirements

Joomla 6.0 or higher.

## Installation

Download `pkg_wmacommunication.zip` from the latest
[release](https://github.com/gestionewma/pkg_wmacommunication/releases) and install it
from **System → Install → Extensions**.

## Updates

After the first install, updates arrive via **System → Update** (Joomla
reads `pkg_wmacommunication_update.xml`).

## Releasing a new version

See [CHECKLIST_RELEASE.md](CHECKLIST_RELEASE.md).

## License

GNU General Public License v2 or later.

---

# WMA Communication — pacchetto Joomla 6

*[English above ↑](#wma-communication--joomla-6-package)*

Form builder avanzato per Joomla 6: **componente** `com_wmacommunication` +
**modulo** `mod_wmacommunication` + **plugin** `plg_content_wmacommunication`,
distribuiti come pacchetto unico `pkg_wmacommunication`.

Permette di creare moduli di contatto complessi in modo visivo (drag & drop,
niente codice), multilingua, con invio email personalizzabile e salvataggio
opzionale degli invii nel database.

## Funzionalità

### Editor visivo dei form

- 20 tipi di campo: testo, email, telefono, URL, numero, textarea, dropdown,
  radio, checkbox, upload file, HTML libero, intestazione, divisore, spazio
  vuoto, hCaptcha, privacy, ufficio (destinatario a scelta), campi automatici
  "URL pagina"/"Titolo articolo", pulsante invio.
- Riordino campi via drag & drop, anteprima del form in tempo reale.
- Per ogni campo: obbligatorietà, larghezza (100/75/66/50/33/25%), etichetta
  nascosta o dentro il campo, placeholder, limiti caratteri/parole, sola
  lettura, e opzioni specifiche per tipo (min/max numerici, opzioni per
  dropdown/radio/checkbox, formato ufficio `Nome|email`, ecc.).
- Moduli di esempio installabili con un clic dalla dashboard, pronti da
  personalizzare.

### Multilingua nativo

- Le traduzioni si gestiscono **dentro il form**: nessuna costante di
  linguaggio, nessuna sostituzione lingua di Joomla da configurare a parte.
- Selettore di lingua nell'editor: si compila la lingua base normalmente, poi
  si passa alle altre lingue e si traduce ciò che serve — un campo lasciato
  vuoto ricade automaticamente sul testo della lingua base.
- Su siti monolingua il selettore sparisce e si scrive direttamente.

### Invio email

- Destinatari configurabili (A, Cc, Ccn), "Reply-To" automatico dal campo
  email del form, invio a un ufficio/reparto scelto dal visitatore tramite
  campo "ufficio", nome mittente personalizzato.
- Corpo email e messaggio di conferma completamente personalizzabili, con
  segnaposto `{Etichetta}` (case-insensitive) sostituiti con i valori
  inseriti dal visitatore — bottoni pronti nell'editor per inserirli con un
  clic.
- Pulsanti Link e Immagine per inserire markup HTML sicuro nel corpo email.
- Anteprima renderizzata del corpo email prima di salvare.
- **Libreria di template messaggio**: template HTML condivisi tra tutti i
  form (con alcuni di serie pronti all'uso), caricabili nel corpo email con
  un clic, esportabili/importabili singolarmente.

### Salvataggio invii su database (opzionale, per form)

- Per ogni form si sceglie la modalità di consegna: solo email (default),
  email + salvataggio nel database, o solo database (nessuna email).
- Sezione admin dedicata **"Invii ricevuti"**: elenco filtrabile per form e
  per stato letto/non letto, ricerca testuale, dettaglio completo di ogni
  invio (con link agli eventuali allegati), esportazione CSV, contatore invii
  non letti in dashboard.
- Colonne configurabili in elenco: sui campi di tipo testo/email si può
  scegliere se e in quale colonna (1 o 2) mostrare il valore quando la lista
  è filtrata su un form specifico.
- Conservazione (retention) configurabile in giorni, come per gli allegati
  (0 = non cancellare mai).

### Allegati

- Upload file con storage **privato fuori dal web root** (quando l'hosting lo
  consente, altrimenti fallback protetto con `.htaccess`/`web.config`), mai
  raggiungibili direttamente via URL.
- Download solo tramite link con token univoco, nessun login richiesto.
- Filtri di sicurezza: denylist di estensioni pericolose sempre attiva,
  allowlist configurabile, controllo del contenuto reale del file, limite di
  dimensione.
- Retention configurabile (giorni), 0 = mai cancellare.

### Campi automatici

- **URL pagina**: l'indirizzo completo della pagina in cui si trova il form,
  compilato da Joomla senza intervento dell'utente.
- **Titolo articolo**: il titolo dell'articolo in cui è inserito il form
  (funziona da voce di menu diretta, da modulo in posizione su pagina
  articolo, o da `{loadposition}` nel testo — richiede il plugin incluso nel
  pacchetto).
- Visibili in sola lettura sul form di default (disattivabile per campo),
  valore comunque sempre incluso in email/invii salvati.

### Sicurezza

- Protezione anti-spam via hCaptcha (chiave configurabile nelle opzioni del
  componente).
- Campo Privacy con checkbox obbligatoria, testo e URL personalizzabili e
  traducibili per lingua.
- Validazione dei campi sia lato server che lato client (email, URL,
  telefono, numero con min/max, scelte ammesse).
- Token CSRF su ogni azione, controlli ACL sui task amministrativi, upload
  protetto contro path traversal e file eseguibili.

### Modulo `mod_wmacommunication`

Espone un form pubblicato come modulo in qualunque posizione del template,
con lo stesso motore di rendering del componente (multilingua incluso).

### Plugin `plg_content_wmacommunication`

Cattura il titolo dell'articolo corrente per alimentare il campo automatico
"Titolo articolo"; si attiva da solo, nessuna configurazione richiesta.

### Aggiornamenti automatici

Una volta installato il pacchetto, le nuove versioni vengono rilevate da
**Sistema → Aggiorna** e mostrano anche il changelog dettagliato (badge
cliccabile in "Gestione estensioni") per componente, modulo, plugin e
pacchetto.

## Struttura repo

```
com_wmacommunication/          sorgente del componente (wmacommunication.xml alla radice)
mod_wmacommunication/          sorgente del modulo
plg_content_wmacommunication/  plugin di contenuto (cattura titolo articolo per i campi automatici)
pkg_wmacommunication/          manifest + script + lingue del pacchetto
pkg_wmacommunication_update.xml   update server (servito via raw.githubusercontent)
changelog.xml                     changelog nativo Joomla (badge in Gestione estensioni)
.github/workflows/release.yml     build automatica dello ZIP a ogni release
```

## Requisiti

Joomla 6.0 o superiore.

## Installazione

Scarica `pkg_wmacommunication.zip` dall'ultima
[release](https://github.com/gestionewma/pkg_wmacommunication/releases) e installalo
da **Sistema → Installa → Estensioni**.

## Aggiornamenti

Dopo la prima installazione, gli aggiornamenti arrivano da
**Sistema → Aggiorna** (Joomla legge `pkg_wmacommunication_update.xml`).

## Rilascio di una nuova versione

Vedi [CHECKLIST_RELEASE.md](CHECKLIST_RELEASE.md).

## Licenza

GNU General Public License v2 o successiva.
