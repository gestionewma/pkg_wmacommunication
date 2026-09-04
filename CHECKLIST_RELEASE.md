# Checklist release — pkg_wmacommunication

Passi per pubblicare una nuova versione con aggiornamento automatico da GitHub.

## 1. Modifiche locali (PC)

Incrementa la versione (es. `2.1.7`) **negli stessi punti di sempre**:

- `com_wmacommunication/wmacommunication.xml` → `<version>`
- `mod_wmacommunication/mod_wmacommunication.xml` → `<version>`
- `plg_content_wmacommunication/wmacommunication.xml` → `<version>`
- `pkg_wmacommunication/pkg_wmacommunication.xml` → `<version>`
- `com_wmacommunication/media/joomla.asset.json` → `"version"`
- fallback nei 2 `WmaInfoField.php` + `Dashboard/HtmlView.php` (`$version` / `'version'`)
- `pkg_wmacommunication_update.xml` → `<version>` **e** i due URL `v2.1.7`
- `changelog.xml` → aggiungi in cima un nuovo blocco `<CHANGELOG><VERSION>2.1.7</VERSION>…</CHANGELOG>`
  con le novità della versione (compare nel popup "Changelog" di Joomla, sia nella
  lista Gestione estensioni sia nella schermata di aggiornamento)

## 2. Push del codice su GitHub

```bash
git add .
git commit -m "Versione 2.1.7"
git push origin main
```

## 3. Release su GitHub

- GitHub → **Releases** → *Draft a new release*
- Tag: `v2.1.7` (target `main`) → *Publish release*
- Attendi ~1 minuto: la GitHub Action crea e allega
  `pkg_wmacommunication-v2.1.7.zip` e `pkg_wmacommunication.zip`

## 4. Checksum (facoltativo ma consigliato)

Scarica `pkg_wmacommunication-v2.1.7.zip`, calcola l'hash SHA256
(l'Action lo stampa già nei log), inseriscilo in
`pkg_wmacommunication_update.xml` → `<checksum type="sha256">…</checksum>`.

```bash
git add pkg_wmacommunication_update.xml
git commit -m "SHA256 v2.1.7"
git push origin main
```

## 5. Test aggiornamento da Joomla

**Sistema → Aggiorna** → *Svuota cache* → *Trova aggiornamenti* → installa.
