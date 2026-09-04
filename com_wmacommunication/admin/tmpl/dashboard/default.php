<?php
/**
 * @package     Wma.Component.Wmacommunication
 * @subpackage  com_wmacommunication
 *
 * @author      WMA Web Maker Agency
 * @copyright   (C) 2026 WMA Web Maker Agency
 * @license     GNU General Public License version 2 or later
 * @link        https://www.webmakeragency.com
 * @version     2.1.0
 * @date        03/07/2026
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
?>

<?php
$c            = $this->credits;
$creditUrlOk  = filter_var($c['authorUrl'], FILTER_VALIDATE_URL)
    && in_array(strtolower((string) parse_url($c['authorUrl'], PHP_URL_SCHEME)), ['http', 'https'], true);
$creditMailOk = (bool) filter_var($c['authorEmail'], FILTER_VALIDATE_EMAIL);
?>
<div class="wma-dashboard">

    <div class="wma-dashboard-top">

        <div class="wma-dashboard-grid">
            <a href="<?= Route::_('index.php?option=com_config&view=component&component=com_wmacommunication') ?>" class="wma-dashboard-card">
                <i class="fa fa-cog"></i>
                <span><?= Text::_('COM_WMACOMMUNICATION_DASHBOARD_OPTIONS') ?></span>
            </a>
            <a href="<?= Route::_('index.php?option=com_wmacommunication&view=forms') ?>" class="wma-dashboard-card">
                <i class="fa fa-envelope-open-text"></i>
                <span><?= Text::_('COM_WMACOMMUNICATION_DASHBOARD_FORMS') ?></span>
            </a>
        </div>

        <div class="wma-dashboard-info">
            <img class="wma-dashboard-info-logo" src="<?= Uri::root() ?>media/com_wmacommunication/images/logo-wma.png" alt="WMA Web Maker Agency">
            <table>
                <tbody>
                    <tr><th><?= Text::_('COM_WMACOMMUNICATION_CREDITS_VERSION') ?></th><td><strong><?= $this->escape($c['version']) ?></strong> &mdash; <?= $this->escape($c['creationDate']) ?></td></tr>
                    <tr><th><?= Text::_('COM_WMACOMMUNICATION_CREDITS_AUTHOR') ?></th><td><?= $this->escape($c['author']) ?></td></tr>
                    <?php if ($creditMailOk) : ?>
                    <tr><th><?= Text::_('COM_WMACOMMUNICATION_CREDITS_EMAIL') ?></th><td><a href="mailto:<?= $this->escape($c['authorEmail']) ?>"><?= $this->escape($c['authorEmail']) ?></a></td></tr>
                    <?php endif; ?>
                    <?php if ($creditUrlOk) : ?>
                    <tr><th><?= Text::_('COM_WMACOMMUNICATION_CREDITS_WEBSITE') ?></th><td><a href="<?= $this->escape($c['authorUrl']) ?>" target="_blank" rel="noopener noreferrer"><?= $this->escape($c['authorUrl']) ?></a></td></tr>
                    <?php endif; ?>
                    <tr><th><?= Text::_('COM_WMACOMMUNICATION_CREDITS_LICENSE') ?></th><td>GNU GPL v2+</td></tr>
                    <tr><th><?= Text::_('COM_WMACOMMUNICATION_CREDITS_COPYRIGHT') ?></th><td><?= $this->escape($c['copyright']) ?></td></tr>
                </tbody>
            </table>
        </div>

    </div>

    <?php $att = $this->attachments; if (!empty($att['path'])) : ?>
        <div class="wma-dashboard-attachments<?= $att['inside_docroot'] ? ' is-warning' : '' ?>">
            <strong><?= Text::_('COM_WMACOMMUNICATION_DASHBOARD_ATTACHMENTS_PATH') ?>:</strong>
            <code><?= $this->escape($att['path']) ?></code>
            <?php if (!$att['exists']) : ?>
                <span class="wma-dashboard-attachments-note"><?= Text::_('COM_WMACOMMUNICATION_DASHBOARD_ATTACHMENTS_MISSING') ?></span>
            <?php elseif ($att['inside_docroot']) : ?>
                <span class="wma-dashboard-attachments-note"><?= Text::_('COM_WMACOMMUNICATION_DASHBOARD_ATTACHMENTS_INSIDE') ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="wma-dashboard-docs">

        <ul class="nav nav-tabs" id="langTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="it-tab" data-bs-toggle="tab" data-bs-target="#it" type="button" role="tab" aria-controls="it" aria-selected="true">IT</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="en-tab" data-bs-toggle="tab" data-bs-target="#en" type="button" role="tab" aria-controls="en" aria-selected="false">EN</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="fr-tab" data-bs-toggle="tab" data-bs-target="#fr" type="button" role="tab" aria-controls="fr" aria-selected="false">FR</button>
            </li>
        </ul>

        <div class="tab-content">

            <!-- ITALIANO -->
            <div class="tab-pane active" id="it" role="tabpanel" aria-labelledby="it-tab">

                <div class="wma-dashboard-doc-section">
                    <h4>Guida all'uso: Come creare il tuo modulo di contatto</h4>
                    <p>Questo strumento ti permette di creare moduli di contatto avanzati in modo semplice e visivo. Ecco i passi da seguire per configurare il tuo modulo:</p>

                    <h5>Aggiungere e organizzare i campi</h5>
                    <p><strong>Inserisci un campo:</strong> Nella scheda a sinistra ("Aggiungi campo"), clicca sui campi che ti servono per inserirli nel modulo.</p>
                    <p><strong>Riordina i campi:</strong> Nella scheda "Tutti i campi", trascina gli elementi per disporli nell'ordine che preferisci.</p>
                    <p><strong>Personalizza ogni campo:</strong> Clicca sul singolo campo per aprire le sue opzioni. Da qui puoi:</p>
                    <ul>
                        <li>Scegliere se renderlo obbligatorio</li>
                        <li>Nascondere il nome del campo (l'etichetta)</li>
                        <li>Mostrare il testo di esempio direttamente all'interno della casella di inserimento</li>
                        <li>Limitare il numero massimo di lettere o parole consentite</li>
                        <li>Impostare il campo in "sola lettura" (l'utente potrà vederlo ma non modificarlo)</li>
                    </ul>

                    <h5>Gestire l'impaginazione e la grafica</h5>
                    <p>Puoi decidere lo spazio occupato da ogni casella per affiancare più elementi sulla stessa riga, selezionando la larghezza in percentuale:</p>
                    <ul>
                        <li><strong>50%</strong>: per visualizzare 2 campi sulla stessa riga</li>
                        <li><strong>33%</strong>: per visualizzare 3 campi sulla stessa riga</li>
                        <li><strong>25%</strong>: per visualizzare 4 campi sulla stessa riga</li>
                    </ul>
                    <p>Mentre lavori, vedrai l'anteprima del modulo comporsi in tempo reale sulla destra. Se una modifica non si aggiorna subito, clicca sul pulsante <strong>Aggiorna anteprima</strong>. Al termine, assegna un nome al tuo modulo in alto a destra e salva.</p>

                    <h5>Utilizzare i modelli pronti (Moduli di esempio)</h5>
                    <p>Se preferisci non iniziare da zero, nella dashboard principale puoi installare alcuni moduli già pronti e modificarli in base alle tue esigenze.</p>
                    <p><strong>Nota importante sui modelli:</strong></p>
                    <ul>
                        <li>I moduli preconfigurati vengono installati come <strong>disattivati</strong>: ricordati di attivarli per poterli usare sul sito</li>
                        <li>Se hai già creato un tuo modulo personalizzato e vuoi installare gli esempi, ti consigliamo di rinominare il tuo modulo con un nome unico (evitando nomi generici come "Contatti"). Se un modello di esempio ha lo stesso nome di un modulo già esistente, l'installazione non andrà a buon fine</li>
                    </ul>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Campi disponibili</h4>
                    <p>18 tipi di campo:</p>
                    <ul>
                        <li><strong>testo</strong> &mdash; campo di input a riga singola</li>
                        <li><strong>email</strong> &mdash; indirizzo email con validazione automatica</li>
                        <li><strong>telefono</strong> &mdash; numero di telefono</li>
                        <li><strong>URL</strong> &mdash; indirizzo web</li>
                        <li><strong>numero</strong> &mdash; campo numerico (min, max, step)</li>
                        <li><strong>textarea</strong> &mdash; area di testo multilinea</li>
                        <li><strong>dropdown</strong> &mdash; menu a tendina per scegliere un'opzione</li>
                        <li><strong>radio</strong> &mdash; pulsanti di scelta (una opzione)</li>
                        <li><strong>checkbox</strong> &mdash; caselle di spunta (più opzioni)</li>
                        <li><strong>file</strong> &mdash; caricamento file dall'utente</li>
                        <li><strong>HTML</strong> &mdash; contenuto HTML libero</li>
                        <li><strong>intestazione</strong> &mdash; titolo H1-H6 per organizzare il form in sezioni</li>
                        <li><strong>divisore</strong> &mdash; linea orizzontale di separazione</li>
                        <li><strong>spazio vuoto</strong> &mdash; spazio verticale per impaginazione</li>
                        <li><strong>hCaptcha</strong> &mdash; verifica antispam (configura nelle Opzioni del componente)</li>
                        <li><strong>privacy</strong> &mdash; checkbox obbligatorio per consenso dati, testo e URL personalizzabili per lingua</li>
                        <li><strong>ufficio</strong> &mdash; menu a tendina per selezionare un ufficio/reparto (formato: <code>Nome Ufficio|email@esempio.it</code>)</li>
                        <li><strong>pulsante invio</strong> &mdash; bottone per inviare il form</li>
                    </ul>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Multilingua</h4>
                    <p>Le traduzioni si gestiscono <strong>dentro il form</strong>: niente costanti <code>COM_...</code>, niente Sostituzioni lingua di Joomla.</p>
                    <p>In alto nell'editor c'è il selettore <strong>"Stai modificando"</strong>. La <strong>lingua base</strong> del form è la lingua predefinita del sito: la compili normalmente. Per le altre lingue seleziona la lingua dal menù e traduci le stringhe testuali (etichetta, segnaposto, opzioni, contenuto, messaggi, privacy). Un campo lasciato vuoto ricade automaticamente sul testo della lingua base.</p>
                    <p>Se il sito è monolingua il selettore non compare e scrivi direttamente il testo.</p>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Campo Privacy</h4>
                    <p>Testo e URL si impostano nelle <strong>Opzioni del campo privacy</strong> (clic sul campo &rarr; scheda Opzioni), e sono traducibili per lingua come gli altri campi:</p>
                    <ul>
                        <li><strong>Testo del consenso</strong> &mdash; testo mostrato accanto al checkbox (es. "Ho letto e accetto l'informativa sulla privacy")</li>
                        <li><strong>URL pagina privacy</strong> &mdash; clicca "Scegli voce di menu" per selezionare la pagina privacy tra le voci di menu del sito. Se l'URL è impostato, il testo del consenso diventa un link; se è vuoto, il testo è mostrato senza link.</li>
                    </ul>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Tab Invio &mdash; Configurazione email</h4>
                    <p>Imposta destinatari (A, Cc, Ccn), Reply-To (seleziona un campo email del form), Office-To (seleziona un campo ufficio) e nome mittente. Ogni campo ha un suggerimento che ne spiega la funzione. Nei tab Invio e Messaggi l'anteprima del form viene nascosta per lasciare più spazio alla configurazione.</p>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Tab Messaggi &mdash; Testi e template</h4>
                    <p>Contiene il <strong>messaggio di conferma</strong> e il <strong>corpo email</strong> per la lingua selezionata nel menù in alto. Sopra il corpo email i pulsanti <strong>Link</strong> e <strong>Immagine</strong> inseriscono il markup HTML.</p>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Segnaposto {&hellip;}</h4>
                    <p>Usa l'etichetta di un campo tra parentesi graffe, es. <code>{Nome}</code> o <code>{Email}</code>, nel messaggio di conferma e nel corpo email: verrà sostituita con il valore inserito dall'utente. Scrivi i segnaposto con l'etichetta della lingua base: funzionano in tutte le lingue.</p>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Pulsanti Link e Immagine</h4>
                    <ul>
                        <li><strong>Link</strong> &mdash; URL, testo, title &rarr; genera link sicuro con <code>rel="noopener noreferrer"</code></li>
                        <li><strong>Immagine</strong> &mdash; URL, descrizione, larghezza &rarr; genera immagine ottimizzata per le email</li>
                    </ul>
                </div>

            </div>

            <!-- ENGLISH -->
            <div class="tab-pane" id="en" role="tabpanel" aria-labelledby="en-tab">

                <div class="wma-dashboard-doc-section">
                    <h4>Usage Guide: How to create your contact form</h4>
                    <p>This tool lets you create advanced contact forms in a simple, visual way. Follow these steps to set up your form:</p>

                    <h5>Adding and organizing fields</h5>
                    <p><strong>Add a field:</strong> In the left panel ("Add field"), click on the fields you need to insert them into the form.</p>
                    <p><strong>Reorder fields:</strong> In the "All fields" tab, drag items to arrange them in your preferred order.</p>
                    <p><strong>Customize each field:</strong> Click on a field to open its options. From here you can:</p>
                    <ul>
                        <li>Choose whether to make it required</li>
                        <li>Hide the field label</li>
                        <li>Show placeholder text inside the input field</li>
                        <li>Limit the maximum number of letters or words allowed</li>
                        <li>Set the field to "read-only" (users can see it but not modify it)</li>
                    </ul>

                    <h5>Layout and appearance</h5>
                    <p>You can decide the width of each field to place multiple elements on the same row by selecting a percentage:</p>
                    <ul>
                        <li><strong>50%</strong>: 2 fields per row</li>
                        <li><strong>33%</strong>: 3 fields per row</li>
                        <li><strong>25%</strong>: 4 fields per row</li>
                    </ul>
                    <p>While you work, you will see the form preview update in real time on the right. If a change does not appear immediately, click <strong>Refresh preview</strong>. When done, give your form a name at the top right and save.</p>

                    <h5>Using ready-made templates (Sample forms)</h5>
                    <p>If you prefer not to start from scratch, you can install pre-built forms from the main dashboard and modify them to suit your needs.</p>
                    <p><strong>Important notes about templates:</strong></p>
                    <ul>
                        <li>Sample forms are installed as <strong>unpublished</strong>: remember to publish them before using them on the site</li>
                        <li>If you already have a custom form and want to install the samples, rename your form with a unique name (avoid generic names like "Contact"). If a sample has the same name as an existing form, the installation will not proceed</li>
                    </ul>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Available Fields</h4>
                    <p>18 field types:</p>
                    <ul>
                        <li><strong>text</strong> &mdash; single-line input field</li>
                        <li><strong>email</strong> &mdash; email address with automatic validation</li>
                        <li><strong>phone</strong> &mdash; phone number</li>
                        <li><strong>URL</strong> &mdash; web address</li>
                        <li><strong>number</strong> &mdash; numeric field (min, max, step)</li>
                        <li><strong>textarea</strong> &mdash; multi-line text area</li>
                        <li><strong>dropdown</strong> &mdash; drop-down menu to choose one option</li>
                        <li><strong>radio</strong> &mdash; radio buttons (single choice)</li>
                        <li><strong>checkbox</strong> &mdash; checkboxes (multiple choices)</li>
                        <li><strong>file</strong> &mdash; file upload from user</li>
                        <li><strong>HTML</strong> &mdash; custom HTML content</li>
                        <li><strong>heading</strong> &mdash; H1-H6 title to organize form sections</li>
                        <li><strong>divider</strong> &mdash; horizontal separation line</li>
                        <li><strong>spacer</strong> &mdash; vertical space for layout</li>
                        <li><strong>hCaptcha</strong> &mdash; anti-spam verification (configure in Component Options)</li>
                        <li><strong>privacy</strong> &mdash; required checkbox for data consent, text and URL customizable per language</li>
                        <li><strong>office</strong> &mdash; drop-down to select an office/department (format: <code>Office Name|email@example.com</code>)</li>
                        <li><strong>submit button</strong> &mdash; button to send the form</li>
                    </ul>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Multilingual</h4>
                    <p>Translations are handled <strong>inside the form</strong>: no <code>COM_...</code> constants, no Joomla Language Overrides.</p>
                    <p>At the top of the editor there is the <strong>"Editing"</strong> selector. The form's <strong>base language</strong> is the site default language: you fill it in normally. For the other languages, pick the language from the menu and translate the text strings (label, placeholder, options, content, messages, privacy). A field left empty automatically falls back to the base-language text.</p>
                    <p>On a monolingual site the selector is hidden and you just type the text.</p>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Privacy Field</h4>
                    <p>Text and URL are set in the <strong>privacy field's Options</strong> (click the field &rarr; Options tab), and are translatable per language like the other fields:</p>
                    <ul>
                        <li><strong>Consent text</strong> &mdash; text shown next to the checkbox (e.g. "I have read and accept the privacy policy")</li>
                        <li><strong>Privacy page URL</strong> &mdash; click "Choose menu item" to select the privacy page from your site's menu. If the URL is set, the consent text becomes a link; if empty, the text is shown without a link.</li>
                    </ul>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Sending Tab &mdash; Email Configuration</h4>
                    <p>Set recipients (To, Cc, Ccn), Reply-To (select an email field from the form), Office-To (select an office field), and sender name. Each field has a hint explaining its function. In the Sending and Messages tabs, the form preview is hidden to leave more room for configuration.</p>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Messages Tab &mdash; Texts and Templates</h4>
                    <p>Holds the <strong>success message</strong> and the <strong>email body</strong> for the language selected in the top menu. Above the email body, the <strong>Link</strong> and <strong>Image</strong> buttons insert HTML markup.</p>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Placeholders {&hellip;}</h4>
                    <p>Use a field label in curly braces, e.g. <code>{Name}</code> or <code>{Email}</code>, in the success message and email body: it will be replaced with the user's submitted value. Write placeholders using the base-language label: they work in every language.</p>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Link and Image Buttons</h4>
                    <ul>
                        <li><strong>Link</strong> &mdash; URL, text, title &rarr; generates a safe link with <code>rel="noopener noreferrer"</code></li>
                        <li><strong>Image</strong> &mdash; URL, alt text, width &rarr; generates an image optimized for emails</li>
                    </ul>
                </div>

            </div>

            <!-- FRANÇAIS -->
            <div class="tab-pane" id="fr" role="tabpanel" aria-labelledby="fr-tab">

                <div class="wma-dashboard-doc-section">
                    <h4>Guide d'utilisation : Comment créer votre formulaire de contact</h4>
                    <p>Cet outil vous permet de créer des formulaires de contact avancés de manière simple et visuelle. Suivez ces étapes pour configurer votre formulaire :</p>

                    <h5>Ajouter et organiser les champs</h5>
                    <p><strong>Ajouter un champ :</strong> Dans le panneau de gauche ("Ajouter un champ"), cliquez sur les champs dont vous avez besoin pour les insérer dans le formulaire.</p>
                    <p><strong>Réorganiser les champs :</strong> Dans l'onglet "Tous les champs", faites glisser les éléments pour les disposer dans l'ordre de votre choix.</p>
                    <p><strong>Personnaliser chaque champ :</strong> Cliquez sur un champ pour ouvrir ses options. Vous pouvez :</p>
                    <ul>
                        <li>Choisir de le rendre obligatoire</li>
                        <li>Masquer le nom du champ (l'étiquette)</li>
                        <li>Afficher un texte d'exemple à l'intérieur de la zone de saisie</li>
                        <li>Limiter le nombre maximal de lettres ou de mots autorisés</li>
                        <li>Définir le champ en "lecture seule" (l'utilisateur peut le voir mais pas le modifier)</li>
                    </ul>

                    <h5>Gérer la mise en page</h5>
                    <p>Vous pouvez décider de la largeur de chaque champ pour aligner plusieurs éléments sur la même ligne en sélectionnant un pourcentage :</p>
                    <ul>
                        <li><strong>50%</strong> : 2 champs par ligne</li>
                        <li><strong>33%</strong> : 3 champs par ligne</li>
                        <li><strong>25%</strong> : 4 champs par ligne</li>
                    </ul>
                    <p>Pendant que vous travaillez, l'aperçu du formulaire se met à jour en temps réel sur la droite. Si une modification ne s'affiche pas immédiatement, cliquez sur <strong>Rafraîchir l'aperçu</strong>. Une fois terminé, donnez un nom à votre formulaire en haut à droite et enregistrez.</p>

                    <h5>Utiliser les modèles prêts (Formulaires d'exemple)</h5>
                    <p>Si vous préférez ne pas partir de zéro, vous pouvez installer des formulaires préconfigurés depuis le tableau de bord principal et les modifier selon vos besoins.</p>
                    <p><strong>Remarque importante sur les modèles :</strong></p>
                    <ul>
                        <li>Les formulaires préconfigurés sont installés comme <strong>désactivés</strong> : pensez à les activer avant de les utiliser sur le site</li>
                        <li>Si vous avez déjà créé votre propre formulaire et souhaitez installer les exemples, renommez votre formulaire avec un nom unique (évitez les noms génériques comme "Contact"). Si un modèle d'exemple porte le même nom qu'un formulaire existant, l'installation ne se fera pas</li>
                    </ul>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Champs disponibles</h4>
                    <p>18 types de champ :</p>
                    <ul>
                        <li><strong>texte</strong> &mdash; champ de saisie sur une seule ligne</li>
                        <li><strong>email</strong> &mdash; adresse email avec validation automatique</li>
                        <li><strong>téléphone</strong> &mdash; numéro de téléphone</li>
                        <li><strong>URL</strong> &mdash; adresse web</li>
                        <li><strong>nombre</strong> &mdash; champ numérique (min, max, step)</li>
                        <li><strong>zone de texte</strong> &mdash; zone de texte multiligne</li>
                        <li><strong>liste déroulante</strong> &mdash; menu déroulant pour choisir une option</li>
                        <li><strong>radio</strong> &mdash; boutons radio (choix unique)</li>
                        <li><strong>case à cocher</strong> &mdash; cases à cocher (choix multiples)</li>
                        <li><strong>fichier</strong> &mdash; téléchargement de fichier par l'utilisateur</li>
                        <li><strong>HTML</strong> &mdash; contenu HTML libre</li>
                        <li><strong>en-tête</strong> &mdash; titre H1-H6 pour organiser le formulaire en sections</li>
                        <li><strong>séparateur</strong> &mdash; ligne horizontale de séparation</li>
                        <li><strong>espace</strong> &mdash; espace vertical pour la mise en page</li>
                        <li><strong>hCaptcha</strong> &mdash; vérification anti-spam (configurer dans les Options du composant)</li>
                        <li><strong>confidentialité</strong> &mdash; case à cocher obligatoire pour le consentement aux données, texte et URL personnalisables par langue</li>
                        <li><strong>bureau</strong> &mdash; menu déroulant pour sélectionner un bureau/service (format : <code>Nom du Bureau|email@exemple.fr</code>)</li>
                        <li><strong>bouton d'envoi</strong> &mdash; bouton pour envoyer le formulaire</li>
                    </ul>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Multilingue</h4>
                    <p>Les traductions se gèrent <strong>dans le formulaire</strong> : pas de constantes <code>COM_...</code>, pas de Substitutions de langue Joomla.</p>
                    <p>En haut de l'éditeur se trouve le sélecteur <strong>"Modification"</strong>. La <strong>langue de base</strong> du formulaire est la langue par défaut du site : vous la remplissez normalement. Pour les autres langues, choisissez la langue dans le menu et traduisez les chaînes de texte (libellé, espace réservé, options, contenu, messages, confidentialité). Un champ laissé vide revient automatiquement au texte de la langue de base.</p>
                    <p>Sur un site monolingue, le sélecteur est masqué et vous saisissez directement le texte.</p>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Champ Confidentialité</h4>
                    <p>Le texte et l'URL se définissent dans les <strong>Options du champ confidentialité</strong> (clic sur le champ &rarr; onglet Options), et sont traduisibles par langue comme les autres champs :</p>
                    <ul>
                        <li><strong>Texte du consentement</strong> &mdash; texte affiché à côté de la case à cocher (ex. "J'ai lu et j'accepte la politique de confidentialité")</li>
                        <li><strong>URL de la page confidentialité</strong> &mdash; cliquez "Choisir un élément de menu" pour sélectionner la page de confidentialité parmi les menus du site. Si l'URL est définie, le texte du consentement devient un lien ; si elle est vide, le texte est affiché sans lien.</li>
                    </ul>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Onglet Envoi &mdash; Configuration email</h4>
                    <p>Définissez les destinataires (À, Cc, Cci), Reply-To (sélectionnez un champ email du formulaire), Office-To (sélectionnez un champ bureau) et le nom de l'expéditeur. Chaque champ a une astuce expliquant sa fonction. Dans les onglets Envoi et Messages, l'aperçu du formulaire est masqué pour laisser plus de place à la configuration.</p>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Onglet Messages &mdash; Textes et modèles</h4>
                    <p>Contient le <strong>message de confirmation</strong> et le <strong>corps d'email</strong> pour la langue sélectionnée dans le menu en haut. Au-dessus du corps d'email, les boutons <strong>Lien</strong> et <strong>Image</strong> insèrent le balisage HTML.</p>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Espaces réservés {&hellip;}</h4>
                    <p>Utilisez le libellé d'un champ entre accolades, ex. <code>{Nom}</code> ou <code>{Email}</code>, dans le message de confirmation et le corps d'email : il sera remplacé par la valeur saisie par l'utilisateur. Écrivez les espaces réservés avec le libellé de la langue de base : ils fonctionnent dans toutes les langues.</p>
                </div>

                <div class="wma-dashboard-doc-section">
                    <h4>Boutons Lien et Image</h4>
                    <ul>
                        <li><strong>Lien</strong> &mdash; URL, texte, titre &rarr; génère un lien sécurisé avec <code>rel="noopener noreferrer"</code></li>
                        <li><strong>Image</strong> &mdash; URL, description, largeur &rarr; génère une image optimisée pour les emails</li>
                    </ul>
                </div>

            </div>

        </div>

    </div>

</div>