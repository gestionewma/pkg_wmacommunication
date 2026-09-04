# WMA Communication — pacchetto Joomla 6

Form builder avanzato per Joomla 6: **componente** `com_wmacommunication` +
**modulo** `mod_wmacommunication` + **plugin** `plg_content_wmacommunication`,
distribuiti come pacchetto unico `pkg_wmacommunication`.

## Struttura repo

```
com_wmacommunication/          sorgente del componente (wmacommunication.xml alla radice)
mod_wmacommunication/          sorgente del modulo
plg_content_wmacommunication/  plugin di contenuto (cattura titolo articolo per i campi automatici)
pkg_wmacommunication/          manifest + script + lingue del pacchetto
pkg_wmacommunication_update.xml   update server (servito via raw.githubusercontent)
.github/workflows/release.yml     build automatica dello ZIP a ogni release
```

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
