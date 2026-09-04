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

namespace Wma\Component\Wmacommunication\Administrator\View\Form;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Factory;

class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $item;
    protected $state;
    protected $fieldTypes;
    protected $checkedOut;
    protected $languages;

    public function display($tpl = null): void
    {
        $wa = $this->document->getWebAssetManager();
        $wa->useStyle('com_wmacommunication.admin');
        $wa->useScript('com_wmacommunication.editor');

        $this->form       = $this->get('Form');
        $this->item       = $this->get('Item');
        $this->state      = $this->get('State');
        $this->fieldTypes = $this->getFieldTypes();
        $this->languages  = LanguageHelper::getLanguages();

        $userId           = Factory::getApplication()->getIdentity()->id;
        $checkedOut       = (int) ($this->item->checked_out ?? 0);
        $this->checkedOut = $checkedOut > 0 && $checkedOut !== $userId;

        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // Stringhe JS
        Text::script('COM_WMACOMMUNICATION_EDITOR_NO_FIELDS');
        Text::script('COM_WMACOMMUNICATION_EDITOR_SELECT_FIELD');
        Text::script('COM_WMACOMMUNICATION_EDITOR_PREVIEW_EMPTY');
        Text::script('COM_WMACOMMUNICATION_SELECT_PLACEHOLDER');
        Text::script('COM_WMACOMMUNICATION_FIELD_TEXT');
        Text::script('COM_WMACOMMUNICATION_FIELD_EMAIL');
        Text::script('COM_WMACOMMUNICATION_FIELD_TEXTAREA');
        Text::script('COM_WMACOMMUNICATION_FIELD_NUMBER');
        Text::script('COM_WMACOMMUNICATION_FIELD_TEL');
        Text::script('COM_WMACOMMUNICATION_FIELD_URL');
        Text::script('COM_WMACOMMUNICATION_FIELD_DROPDOWN');
        Text::script('COM_WMACOMMUNICATION_FIELD_RADIO');
        Text::script('COM_WMACOMMUNICATION_FIELD_CHECKBOX');
        Text::script('COM_WMACOMMUNICATION_FIELD_FILEUPLOAD');
        Text::script('COM_WMACOMMUNICATION_FIELD_HTML');
        Text::script('COM_WMACOMMUNICATION_FIELD_HEADING');
        Text::script('COM_WMACOMMUNICATION_FIELD_DIVIDER');
        Text::script('COM_WMACOMMUNICATION_FIELD_EMPTYSPACE');
        Text::script('COM_WMACOMMUNICATION_FIELD_HCAPTCHA');
        Text::script('COM_WMACOMMUNICATION_FIELD_SUBMIT');
        Text::script('COM_WMACOMMUNICATION_FIELD_PRIVACY');
        Text::script('COM_WMACOMMUNICATION_FIELD_OFFICE');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_LABEL');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_LABEL_HINT');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_PLACEHOLDER');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_REQUIRED');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_HIDELABEL');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_LABELINSIDE');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_READONLY');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_WIDTH');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_MINCHARS');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_MAXCHARS');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_MINWORDS');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_MAXWORDS');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_ROWS');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_MIN');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_MAX');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_STEP');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_INPUTMASK');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_OPTIONS');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_LAYOUT');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_UPLOAD_FOLDER');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_UPLOAD_FOLDER_HINT');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_MAX_FILE_SIZE');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_MAX_FILE_SIZE_HINT');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_UPLOAD_TYPES');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_UPLOAD_TYPES_HINT');
        Text::script('COM_WMACOMMUNICATION_EDITOR_UPLOAD_INFO');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_CONTENT');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_LEVEL');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_ALIGNMENT');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_BORDER_STYLE');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_BORDER_WIDTH');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_BORDER_COLOR');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_HEIGHT');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_HCAPTCHA_TYPE');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_THEME');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_SIZE');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_SUBMIT_TEXT');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_OFFICE_OPTIONS');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OFFICE_INFO');
        Text::script('COM_WMACOMMUNICATION_EDITOR_LAYOUT_1COL');
        Text::script('COM_WMACOMMUNICATION_EDITOR_LAYOUT_2COL');
        Text::script('COM_WMACOMMUNICATION_EDITOR_LAYOUT_3COL');
        Text::script('COM_WMACOMMUNICATION_EDITOR_LAYOUT_SIDE');
        Text::script('COM_WMACOMMUNICATION_EDITOR_ALIGN_LEFT');
        Text::script('COM_WMACOMMUNICATION_EDITOR_ALIGN_CENTER');
        Text::script('COM_WMACOMMUNICATION_EDITOR_ALIGN_RIGHT');
        Text::script('COM_WMACOMMUNICATION_EDITOR_BORDER_SOLID');
        Text::script('COM_WMACOMMUNICATION_EDITOR_BORDER_DASHED');
        Text::script('COM_WMACOMMUNICATION_EDITOR_BORDER_DOTTED');
        Text::script('COM_WMACOMMUNICATION_EDITOR_HCAPTCHA_CHECKBOX');
        Text::script('COM_WMACOMMUNICATION_EDITOR_HCAPTCHA_INVISIBLE');
        Text::script('COM_WMACOMMUNICATION_EDITOR_THEME_LIGHT');
        Text::script('COM_WMACOMMUNICATION_EDITOR_THEME_DARK');
        Text::script('COM_WMACOMMUNICATION_EDITOR_SIZE_NORMAL');
        Text::script('COM_WMACOMMUNICATION_EDITOR_SIZE_COMPACT');
        Text::script('COM_WMACOMMUNICATION_EDITOR_WIDTH_FULL');
        Text::script('COM_WMACOMMUNICATION_EDITOR_WIDTH_3_4');
        Text::script('COM_WMACOMMUNICATION_EDITOR_WIDTH_2_3');
        Text::script('COM_WMACOMMUNICATION_EDITOR_WIDTH_1_2');
        Text::script('COM_WMACOMMUNICATION_EDITOR_WIDTH_1_3');
        Text::script('COM_WMACOMMUNICATION_EDITOR_WIDTH_1_4');
        Text::script('COM_WMACOMMUNICATION_SUBMIT');
        Text::script('COM_WMACOMMUNICATION_FIELD_DEFAULT_UPLOAD_FOLDER');
        Text::script('COM_WMACOMMUNICATION_FIELD_DEFAULT_UPLOAD_TYPES');
        Text::script('COM_WMACOMMUNICATION_FORM_TITLE_REQUIRED');
        Text::script('COM_WMACOMMUNICATION_EDITOR_TRANSLATING_NOTE');

        // Stringhe usate da editor.js
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_PRIVACY_TEXT');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_PRIVACY_URL');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_PRIVACY_URL_BUTTON');
        Text::script('COM_WMACOMMUNICATION_EDITOR_OPT_PRIVACY_URL_HINT');
        Text::script('COM_WMACOMMUNICATION_FIELD_PRIVACY_DEFAULT');
        Text::script('COM_WMACOMMUNICATION_MESSAGES_LINK_PROMPT_URL');
        Text::script('COM_WMACOMMUNICATION_MESSAGES_LINK_PROMPT_TEXT');
        Text::script('COM_WMACOMMUNICATION_MESSAGES_LINK_PROMPT_TITLE');
        Text::script('COM_WMACOMMUNICATION_MESSAGES_LOGO_PROMPT_URL');
        Text::script('COM_WMACOMMUNICATION_MESSAGES_LOGO_PROMPT_ALT');
        Text::script('COM_WMACOMMUNICATION_MESSAGES_LOGO_PROMPT_WIDTH');

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $isNew = ($this->item->id == 0);

        ToolbarHelper::title(
            $isNew ? Text::_('COM_WMACOMMUNICATION_FORM_NEW') : Text::_('COM_WMACOMMUNICATION_FORM_EDIT'),
            'pencil-2'
        );

        $toolbar = Toolbar::getInstance();

        $toolbar->apply('form.apply');
        $toolbar->save('form.save');
        $toolbar->cancel('form.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }

    protected function getFieldTypes(): array
    {
        return [
            'text'       => ['label' => 'COM_WMACOMMUNICATION_FIELD_TEXT',       'icon' => 'fa-font'],
            'email'      => ['label' => 'COM_WMACOMMUNICATION_FIELD_EMAIL',      'icon' => 'fa-envelope'],
            'textarea'   => ['label' => 'COM_WMACOMMUNICATION_FIELD_TEXTAREA',   'icon' => 'fa-align-left'],
            'number'     => ['label' => 'COM_WMACOMMUNICATION_FIELD_NUMBER',     'icon' => 'fa-hashtag'],
            'tel'        => ['label' => 'COM_WMACOMMUNICATION_FIELD_TEL',        'icon' => 'fa-phone'],
            'url'        => ['label' => 'COM_WMACOMMUNICATION_FIELD_URL',        'icon' => 'fa-globe'],
            'dropdown'   => ['label' => 'COM_WMACOMMUNICATION_FIELD_DROPDOWN',   'icon' => 'fa-caret-square-o-down'],
            'radio'      => ['label' => 'COM_WMACOMMUNICATION_FIELD_RADIO',      'icon' => 'fa-dot-circle-o'],
            'checkbox'   => ['label' => 'COM_WMACOMMUNICATION_FIELD_CHECKBOX',   'icon' => 'fa-check-square'],
            'fileupload' => ['label' => 'COM_WMACOMMUNICATION_FIELD_FILEUPLOAD', 'icon' => 'fa-upload'],
            'html'       => ['label' => 'COM_WMACOMMUNICATION_FIELD_HTML',       'icon' => 'fa-code'],
            'heading'    => ['label' => 'COM_WMACOMMUNICATION_FIELD_HEADING',    'icon' => 'fa-header'],
            'divider'    => ['label' => 'COM_WMACOMMUNICATION_FIELD_DIVIDER',    'icon' => 'fa-minus'],
            'emptyspace' => ['label' => 'COM_WMACOMMUNICATION_FIELD_EMPTYSPACE', 'icon' => 'fa-arrows-v'],
            'privacy'    => ['label' => 'COM_WMACOMMUNICATION_FIELD_PRIVACY',    'icon' => 'fa-lock'],
            'office'     => ['label' => 'COM_WMACOMMUNICATION_FIELD_OFFICE',     'icon' => 'fa-building'],
            'hcaptcha'   => ['label' => 'COM_WMACOMMUNICATION_FIELD_HCAPTCHA',   'icon' => 'fa-shield'],
            'submit'     => ['label' => 'COM_WMACOMMUNICATION_FIELD_SUBMIT',     'icon' => 'fa-paper-plane'],
        ];
    }
}