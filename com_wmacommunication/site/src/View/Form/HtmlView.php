<?php
/**
 * @package     Wma.Component.Wmacommunication
 * @subpackage  com_wmacommunication
 *
 * @author      WMA Web Maker Agency
 * @copyright   (C) 2026 WMA Web Maker Agency
 * @license     GNU General Public License version 2 or later
 * @link        https://www.webmakeragency.com
 * @version     2.0.2
 * @date        02/07/2026
 */

namespace Wma\Component\Wmacommunication\Site\View\Form;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $formData = [];
    protected $formErrors = false;

    /** Mappa traduzioni del form: { langTag: { chiave: valore } } */
    protected $translations = [];

    /** Tag lingua attiva del sito (es. it-IT) */
    protected $langTag = '';

    public function setForm(\stdClass $form): void
    {
        $this->form = $form;
    }

    /**
     * Prepara la view per il rendering dei campi da un contesto esterno (es. il modulo):
     * imposta form, lingua attiva, mappa traduzioni e dati di sessione, senza dispatch completo.
     */
    public function prepareForRender(\stdClass $form): void
    {
        $app = Factory::getApplication();
        // Le lingue del componente sono in components/com_wmacommunication/language/:
        // caricandole da JPATH_SITE Joomla cercherebbe in /language/<lang>/ e fallirebbe
        // in silenzio (le costanti COM_... resterebbero grezze quando il form è reso dal modulo).
        $app->getLanguage()->load('com_wmacommunication', JPATH_SITE . '/components/com_wmacommunication')
            || $app->getLanguage()->load('com_wmacommunication', JPATH_SITE);

        $this->form         = $form;
        $this->langTag      = $app->getLanguage()->getTag();
        $this->translations = $form->settings_decoded['translations'] ?? [];

        $formId           = (int) ($form->id ?? 0);
        $this->formData   = (array) $app->getSession()->get('com_wmacommunication.form_data_' . $formId, []);
        $this->formErrors = !empty($this->formData);
    }

    public function display($tpl = null): void
    {
        $wa = $this->document->getWebAssetManager();
        $wa->useStyle('com_wmacommunication.site');
        $wa->useScript('com_wmacommunication.site.js');

        $app    = Factory::getApplication();
        $formId = $app->input->getInt('id', 0);

        if (!$formId) {
            $menu   = $app->getMenu()->getActive();
            $formId = $menu ? (int) $menu->getParams()->get('id', 0) : 0;
        }

        if (!$formId) {
            throw new \RuntimeException(Text::_('COM_WMACOMMUNICATION_FORM_NOT_FOUND'), 404);
        }

        $model      = $this->getModel();
        $this->form = $model->getForm($formId);

        if (!$this->form) {
            throw new \RuntimeException(Text::_('COM_WMACOMMUNICATION_FORM_NOT_FOUND'), 404);
        }

        $this->prepareForRender($this->form);

        // Carica lo script hCaptcha solo se il form contiene un campo hcaptcha
        $hasHcaptcha = !empty(array_filter(
            $this->form->fields_decoded,
            static fn($f) => ($f['type'] ?? '') === 'hcaptcha'
        ));

        if ($hasHcaptcha) {
            $this->document->addScript(
                'https://js.hcaptcha.com/1/api.js',
                [],
                ['async' => true, 'defer' => true]
            );
        }

        parent::display($tpl);
    }

    /**
     * Risolve una stringa traducibile del form: traduzione della lingua attiva se
     * presente, altrimenti il valore della lingua base (passato in $base).
     */
    public function tr(string $key, string $base): string
    {
        // La lingua base non ha traduzioni: i valori base sono inline
        if ($this->langTag === ($this->translations['_base'] ?? '')) {
            return $base;
        }

        $value = $this->translations[$this->langTag][$key] ?? '';

        return $value !== '' ? $value : $base;
    }

    /**
     * Filtro "safe HTML": lascia passare i tag di formattazione (br, p, a, strong,
     * ul, table, img, ...) ma rimuove script/iframe/object e gli attributi
     * pericolosi (on*, javascript:). NB: InputFilter::getInstance() senza argomenti
     * usa la whitelist VUOTA e cancella OGNI tag — qui serve la modalita' block-list.
     */
    private function safeHtml(string $html): string
    {
        if ($html === '') {
            return '';
        }

        return \Joomla\CMS\Filter\InputFilter::getInstance([], [], 1, 1)->clean($html, 'html');
    }

    /**
     * Descrizione del form (tradotta), filtrata da HTML pericoloso: la traduzione
     * arriva da settings.translations che e' salvato "raw", quindi va ripulita.
     */
    public function safeDescription(): string
    {
        return $this->safeHtml($this->tr('form.description', (string) ($this->form->description ?? '')));
    }

    /**
     * Messaggi di validazione (tradotti) per il JS di frontend, pronti da mettere
     * in un attributo data- del <form>.
     */
    public function validationMessagesJson(): string
    {
        $messages = [
            'required' => Text::_('COM_WMACOMMUNICATION_VALIDATION_REQUIRED'),
            'email'  => Text::_('COM_WMACOMMUNICATION_VALIDATION_EMAIL'),
            'url'    => Text::_('COM_WMACOMMUNICATION_VALIDATION_URL'),
            'tel'    => Text::_('COM_WMACOMMUNICATION_VALIDATION_TEL'),
            'number' => Text::_('COM_WMACOMMUNICATION_VALIDATION_NUMBER'),
            'min'    => Text::_('COM_WMACOMMUNICATION_VALIDATION_MIN'),
            'max'    => Text::_('COM_WMACOMMUNICATION_VALIDATION_MAX'),
            'filetype' => Text::_('COM_WMACOMMUNICATION_VALIDATION_FILETYPE'),
            'filesize' => Text::_('COM_WMACOMMUNICATION_VALIDATION_FILESIZE'),
        ];

        return htmlspecialchars(json_encode($messages, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
    }

    public function renderField(array $field, int $index): string
    {
        $type        = $field['type'] ?? '';
        // uid usato in name/id/for/attributi: consentiti solo [A-Za-z0-9_]
        $uid         = preg_replace('/[^A-Za-z0-9_]/', '', (string) ($field['uid'] ?? ('idx' . $index)));
        $uid         = $uid !== '' ? $uid : ('idx' . $index);
        $name        = 'wma_field_' . $uid;
        $label       = Text::_($this->tr('field.' . $uid . '.label', $field['label'] ?? ''));
        $required    = !empty($field['required']) ? 'required' : '';
        $readonly    = !empty($field['readonly']) ? 'readonly' : '';
        // width usato in style="width:...": solo un valore della mappa o un numero puro
        $width       = (string) ($field['width'] ?? '100');
        $hidelabel   = !empty($field['hidelabel']);
        $labelinside = !empty($field['labelinside']);
        $placeholder = $labelinside ? $label : Text::_($this->tr('field.' . $uid . '.placeholder', $field['placeholder'] ?? ''));
        $req_mark    = $required ? ' <span class="wma-cf-required">*</span>' : '';

        // Ripopolamento da sessione / errore validazione
        $submittedValue = $this->formData[$name] ?? '';
        if (is_array($submittedValue)) {
            $submittedValue = '';
        }
        $hasError = $this->formErrors && $required && empty($submittedValue);
        $errorClass = $hasError ? ' wma-cf-error' : '';
        $valueAttr = $submittedValue !== '' ? ' value="' . htmlspecialchars($submittedValue) . '"' : '';
        $selectedValue = $submittedValue;

        // Tipi senza un singolo input con id corrispondente: label senza attributo for
        $noForTypes = ['radio', 'checkbox', 'html', 'heading', 'divider', 'emptyspace', 'hcaptcha', 'submit'];
        $labelFor   = !in_array($type, $noForTypes) ? ' for="' . $name . '"' : '';

        $labelHtml = '';
        if (!$hidelabel && !$labelinside) {
            $labelHtml = '<label' . $labelFor . '>' . htmlspecialchars($label) . $req_mark . '</label>';
        }

        $widthMap = [
            '25'  => '25%',
            '33'  => 'calc(100% / 3)',
            '50'  => '50%',
            '66'  => 'calc(200% / 3)',
            '75'  => '75%',
            '100' => '100%',
        ];
        $widthCss = $widthMap[$width] ?? (ctype_digit($width) ? $width . '%' : '100%');

        $out = '<div class="wma-cf-field' . $errorClass . '" style="width:' . $widthCss . '">';

        switch ($type) {

            case 'text':
            case 'email':
            case 'tel':
            case 'url':
                $out .= $labelHtml;
                $out .= '<input type="' . $type . '" name="' . $name . '" id="' . $name . '"'
                    . ' placeholder="' . htmlspecialchars($placeholder) . '"'
                    . $valueAttr
                    . ' ' . $required . ' ' . $readonly . '>';
                break;

            case 'number':
                $min  = ($field['min'] ?? '') !== '' ? ' min="' . (int)$field['min'] . '"' : '';
                $max  = ($field['max'] ?? '') !== '' ? ' max="' . (int)$field['max'] . '"' : '';
                $step = ' step="' . (is_numeric($field['step'] ?? 1) ? $field['step'] : 1) . '"';
                $out .= $labelHtml;
                $out .= '<input type="number" name="' . $name . '" id="' . $name . '"'
                    . ' placeholder="' . htmlspecialchars($placeholder) . '"'
                    . $min . $max . $step
                    . $valueAttr
                    . ' ' . $required . ' ' . $readonly . '>';
                break;

            case 'textarea':
                $rows = (int)($field['rows'] ?? 6);
                $out .= $labelHtml;
                $out .= '<textarea name="' . $name . '" id="' . $name . '"'
                    . ' rows="' . $rows . '"'
                    . ' placeholder="' . htmlspecialchars($placeholder) . '"'
                    . ' ' . $required . ' ' . $readonly . '>'
                    . htmlspecialchars($submittedValue)
                    . '</textarea>';
                break;

            case 'dropdown':
                $out .= $labelHtml;
                $out .= '<select name="' . $name . '" id="' . $name . '" ' . $required . '>';
                $out .= '<option value="">' . Text::_('COM_WMACOMMUNICATION_SELECT_PLACEHOLDER') . '</option>';
                foreach (explode("\n", $this->tr('field.' . $uid . '.options', $field['options'] ?? '')) as $opt) {
                    $opt = trim(Text::_($opt));
                    if ($opt) {
                        $sel = ($selectedValue === $opt) ? ' selected' : '';
                        $out .= '<option value="' . htmlspecialchars($opt) . '"' . $sel . '>' . htmlspecialchars($opt) . '</option>';
                    }
                }
                $out .= '</select>';
                break;

            case 'office': {
                $out .= $labelHtml;
                $out .= '<select name="' . $name . '" id="' . $name . '" ' . $required . '>';
                $out .= '<option value="">' . Text::_('COM_WMACOMMUNICATION_SELECT_PLACEHOLDER') . '</option>';
                foreach (explode("\n", $field['options'] ?? '') as $opt) {
                    $opt = trim($opt);
                    if ($opt) {
                        $parts = explode('|', $opt);
                        $officeName  = htmlspecialchars(trim($parts[0]));
                        $officeEmail = htmlspecialchars(trim($parts[1] ?? ''));
                        $sel = ($selectedValue === $officeEmail) ? ' selected' : '';
                        $out .= '<option value="' . $officeEmail . '"' . $sel . '>' . $officeName . '</option>';
                    }
                }
                $out .= '</select>';
                break;
            }

            case 'radio': {
                $out .= $labelHtml;
                $layout   = in_array($field['choicelayout'] ?? '1', ['1', '2', '3', 'auto'], true) ? $field['choicelayout'] : '1';
                $colClass = $layout === 'auto' ? 'wma-cf-choices-side' : 'wma-cf-choices-col-' . $layout;
                $out .= '<div class="wma-cf-choices ' . $colClass . '">';
                $optIndex = 0;
                foreach (explode("\n", $this->tr('field.' . $uid . '.options', $field['options'] ?? '')) as $opt) {
                    $opt = trim(Text::_($opt));
                    if ($opt) {
                        $optId = $name . '_' . $optIndex++;
                        $out .= '<div><label for="' . $optId . '"><input type="radio" id="' . $optId . '" name="' . $name . '" value="' . htmlspecialchars($opt) . '" ' . $required . '> ' . htmlspecialchars($opt) . '</label></div>';
                    }
                }
                $out .= '</div>';
                break;
            }

            case 'checkbox': {
                $out .= $labelHtml;
                $layout   = in_array($field['choicelayout'] ?? '1', ['1', '2', '3', 'auto'], true) ? $field['choicelayout'] : '1';
                $colClass = $layout === 'auto' ? 'wma-cf-choices-side' : 'wma-cf-choices-col-' . $layout;
                $reqData  = $required ? ' data-wma-required="1"' : '';
                $out .= '<div class="wma-cf-choices ' . $colClass . '"' . $reqData . '>';
                $optIndex = 0;
                foreach (explode("\n", $this->tr('field.' . $uid . '.options', $field['options'] ?? '')) as $opt) {
                    $opt = trim(Text::_($opt));
                    if ($opt) {
                        $optId = $name . '_' . $optIndex++;
                        $out .= '<div><label for="' . $optId . '"><input type="checkbox" id="' . $optId . '" name="' . $name . '[]" value="' . htmlspecialchars($opt) . '"> ' . htmlspecialchars($opt) . '</label></div>';
                    }
                }
                $out .= '</div>';
                break;
            }

            case 'fileupload': {
                $maxSize = (int) ($field['max_file_size'] ?? 0);

                // Lista effettiva di estensioni: quella dell'admin, altrimenti il default sicuro
                $configured = trim((string) ($field['upload_types'] ?? ''));
                $exts = $configured !== ''
                    ? array_filter(array_map(static fn($v) => strtolower(ltrim(trim($v), '.')), explode(',', $configured)))
                    : \Wma\Component\Wmacommunication\Site\Helper\AttachmentHelper::defaultAllowedTypes();

                $extList = implode(', ', $exts);
                $accept  = '.' . implode(',.', $exts);

                $out .= $labelHtml;
                $out .= '<input type="file" name="' . $name . '" id="' . $name . '"'
                    . ' accept="' . htmlspecialchars($accept) . '"'
                    . ' data-wma-accept="' . htmlspecialchars(implode(',', $exts)) . '"'
                    . ' data-wma-maxsize="' . $maxSize . '"'
                    . ' ' . $required . '>';

                $hints = [Text::sprintf('COM_WMACOMMUNICATION_FILE_ALLOWED_TYPES', $extList)];
                if ($maxSize > 0) {
                    $hints[] = Text::sprintf('COM_WMACOMMUNICATION_FILE_MAX_SIZE', $maxSize);
                }
                $out .= '<small class="wma-cf-filetypes">' . htmlspecialchars(implode(' — ', $hints)) . '</small>';
                break;
            }

            case 'html': {
                // Contenuto HTML libero: i tag di formattazione restano, script/handler no.
                $out .= $this->safeHtml(Text::_($this->tr('field.' . $uid . '.content', $field['content'] ?? '')));
                break;
            }

            case 'heading': {
                $tag   = in_array(strtolower((string) ($field['level'] ?? 'h2')), ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true)
                    ? strtolower($field['level']) : 'h2';
                $align = in_array($field['alignment'] ?? 'left', ['left', 'center', 'right'], true)
                    ? $field['alignment'] : 'left';
                $out .= '<' . $tag . ' style="text-align:' . $align . '">' . htmlspecialchars(Text::_($this->tr('field.' . $uid . '.content', $field['content'] ?? ''))) . '</' . $tag . '>';
                break;
            }

            case 'divider': {
                $bStyle = in_array($field['border_style'] ?? 'solid', ['solid', 'dashed', 'dotted', 'double'], true)
                    ? $field['border_style'] : 'solid';
                $bColor = preg_match('/^#[0-9a-fA-F]{3,8}$|^[a-zA-Z]{1,20}$/', (string) ($field['border_color'] ?? ''))
                    ? $field['border_color'] : '#cccccc';
                $style  = 'border-top:' . (int)($field['border_width'] ?? 1) . 'px ' . $bStyle . ' ' . $bColor . ';';
                $out .= '<hr style="' . $style . '">';
                break;
            }

            case 'emptyspace':
                $out .= '<div style="height:' . (int)($field['height'] ?? 20) . 'px"></div>';
                break;

            case 'hcaptcha': {
                $hParams   = \Joomla\CMS\Component\ComponentHelper::getParams('com_wmacommunication');
                $siteKey   = trim($hParams->get('hcaptcha_site_key', ''));
                $secretKey = trim($hParams->get('hcaptcha_secret_key', ''));

                // La secret key inizia sempre con "0x" o "ES_"
                // La site key è in formato UUID (non inizia con "0x"/"ES_")
                $siteKeyIsSecret   = !empty($siteKey)   && (str_starts_with($siteKey, '0x')   || str_starts_with($siteKey, 'ES_'));
                $secretKeyIsSite   = !empty($secretKey) && !str_starts_with($secretKey, '0x') && !str_starts_with($secretKey, 'ES_');

                if (empty($siteKey)) {
                    $out .= '<div class="wma-hcaptcha-notice wma-hcaptcha-notice--error">'
                          . Text::_('COM_WMACOMMUNICATION_HCAPTCHA_NOTICE_NO_SITE_KEY')
                          . '</div>';
                } elseif ($siteKeyIsSecret) {
                    $out .= '<div class="wma-hcaptcha-notice wma-hcaptcha-notice--error">'
                          . Text::_('COM_WMACOMMUNICATION_HCAPTCHA_NOTICE_KEYS_INVERTED')
                          . '</div>';
                } else {
                    $out .= '<div class="h-captcha" data-sitekey="' . htmlspecialchars($siteKey) . '"></div>';

                    if (empty($secretKey)) {
                        $out .= '<div class="wma-hcaptcha-notice wma-hcaptcha-notice--warning">'
                              . Text::_('COM_WMACOMMUNICATION_HCAPTCHA_NOTICE_NO_SECRET_KEY')
                              . '</div>';
                    } elseif ($secretKeyIsSite) {
                        $out .= '<div class="wma-hcaptcha-notice wma-hcaptcha-notice--warning">'
                              . Text::_('COM_WMACOMMUNICATION_HCAPTCHA_NOTICE_KEYS_INVERTED')
                              . '</div>';
                    }
                }
                break;
            }

            case 'privacy':
                // Fallback per form non ancora risalvati: privacy ancora in messages_base
                $legacyPrivacy = $this->form->settings_decoded['messages_base'] ?? [];
                $privacyBaseText = $field['privacy_text'] ?? ($legacyPrivacy['privacy_text'] ?? '');
                $privacyBaseUrl  = $field['privacy_url']  ?? ($legacyPrivacy['privacy_url']  ?? '');

                $privacyText = $this->tr('field.' . $uid . '.privacy_text', $privacyBaseText);
                if ($privacyText === '') {
                    $privacyText = Text::_('COM_WMACOMMUNICATION_FIELD_PRIVACY_DEFAULT');
                }
                $privacyUrl = $this->resolvePrivacyUrl($this->tr('field.' . $uid . '.privacy_url', $privacyBaseUrl));
                $out .= '<div class="wma-cf-privacy">';
                $out .= '<input type="checkbox" name="' . $name . '" id="' . $name . '" data-wma-privacy="1" required>';
                if ($privacyUrl !== '') {
                    $out .= '<span class="wma-cf-privacy-text"><a href="' . htmlspecialchars($privacyUrl) . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($privacyText) . '</a></span>';
                } else {
                    $out .= '<span class="wma-cf-privacy-text">' . htmlspecialchars($privacyText) . '</span>';
                }
                $out .= '</div>';
                break;

            case 'submit':
                $text = Text::_($this->tr('field.' . $uid . '.text', $field['text'] ?? 'COM_WMACOMMUNICATION_SUBMIT'));
                $out .= '<button type="submit" id="' . $name . '" name="' . $name . '" class="pul pul-primary wma-cf-submit">' . htmlspecialchars($text) . '</button>';
                break;

            default:
                $out .= '';
        }

        $out .= '</div>';

        return $out;
    }

    /**
     * Risolve l'URL della privacy in un URL utilizzabile.
     *
     * Per i link a una voce di menu (index.php?Itemid=N) NON si usa Route::_(): in
     * multilingua il build SEF puo' riassegnare l'Itemid a un'altra lingua o a un id
     * inesistente. Si emette l'URL grezzo con l'Itemid della voce (piu' &lang): la
     * direzione di PARSING del router lo risolve sempre alla pagina corretta e, con
     * SEF attivo, Joomla reindirizza all'URL "pulito" al click.
     */
    private function resolvePrivacyUrl(string $url): string
    {
        $url = trim($url);

        if (!str_starts_with($url, 'index.php')) {
            // Solo schemi/percorsi sicuri: http(s), mailto, tel o path relativo. Niente javascript:, data:, ecc.
            if (preg_match('#^(https?:|mailto:|tel:)#i', $url) || str_starts_with($url, '/') || str_starts_with($url, '#')) {
                return $url;
            }

            return '';
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $vars);
        $itemid = isset($vars['Itemid']) ? (int) $vars['Itemid'] : 0;

        if ($itemid <= 0) {
            return Route::_($url);
        }

        $out = Uri::root() . 'index.php?Itemid=' . $itemid;

        if (\Joomla\CMS\Language\Multilanguage::isEnabled()) {
            $menuItem = Factory::getApplication()->getMenu()->getItem($itemid);
            $lang     = ($menuItem !== null && $menuItem->language && $menuItem->language !== '*')
                ? $menuItem->language
                : ($vars['lang'] ?? $this->langTag);
            $out .= '&lang=' . $lang;
        }

        return $out;
    }
}