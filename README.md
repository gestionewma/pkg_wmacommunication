# WMA Communication — pacchetto Joomla 6

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
