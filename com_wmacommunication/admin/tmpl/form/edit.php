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

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$isNew      = ($this->item->id == 0);
$formId     = (int) $this->item->id;
$fields     = json_decode($this->item->fields ?? '[]', true) ?: [];
$checkedOut = $this->checkedOut ?? false;

// Impostazioni del form (JSON) + lingua base
$settingsData = [];
if (!empty($this->item->settings)) {
    $decoded = json_decode($this->item->settings, true);
    $settingsData = is_array($decoded) ? $decoded : [];
}

$siteDefaultLang = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');
$baseLang        = $settingsData['translations']['_base'] ?? $siteDefaultLang;
$multilang       = count($this->languages) > 1;

if ($checkedOut) {
    $checkedOutUser = Factory::getUser((int) $this->item->checked_out);
    $checkedOutName = $checkedOutUser->name ?: $checkedOutUser->username;
}
?>

<?php if ($checkedOut) : ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
    <span class="icon-lock" aria-hidden="true"></span>
    <span>
        <?= Text::sprintf('JLIB_HTML_CHECKED_OUT', htmlspecialchars($checkedOutName)) ?>
    </span>
</div>
<?php endif; ?>

<div class="wma-editor <?= $checkedOut ? 'wma-editor--locked' : '' ?>"
     data-base-lang="<?= $this->escape($baseLang) ?>"
     data-languages='<?= htmlspecialchars(json_encode(array_map(static function ($l) {
        return ['code' => $l->lang_code, 'title' => $l->title];
     }, $this->languages)), ENT_QUOTES, 'UTF-8') ?>'>

    <div class="wma-editor-topbar">
        <div class="wma-editor-tabs">
            <button type="button" class="wma-tab-btn active" data-tab="add-field"><?= Text::_('COM_WMACOMMUNICATION_EDITOR_TAB_ADD') ?></button>
            <button type="button" class="wma-tab-btn" data-tab="all-fields"><?= Text::_('COM_WMACOMMUNICATION_EDITOR_TAB_FIELDS') ?></button>
            <button type="button" class="wma-tab-btn" data-tab="options"><?= Text::_('COM_WMACOMMUNICATION_EDITOR_TAB_OPTIONS') ?></button>
            <button type="button" class="wma-tab-btn" data-tab="sending"><?= Text::_('COM_WMACOMMUNICATION_EDITOR_TAB_SENDING') ?></button>
            <button type="button" class="wma-tab-btn" data-tab="messages"><?= Text::_('COM_WMACOMMUNICATION_EDITOR_TAB_MESSAGES') ?></button>
        </div>
        <div class="wma-editor-topbar-actions">
            <?php if ($multilang) : ?>
            <div class="wma-editor-lang">
                <label for="wma-editor-lang-select"><?= Text::_('COM_WMACOMMUNICATION_EDITOR_EDITING_LANG') ?></label>
                <select id="wma-editor-lang-select">
                    <option value="__base__"><?= Text::sprintf('COM_WMACOMMUNICATION_EDITOR_LANG_BASE_OPTION', $this->escape($baseLang)) ?></option>
                    <?php foreach ($this->languages as $l) : ?>
                        <?php if ($l->lang_code === $baseLang) { continue; } ?>
                        <option value="<?= $this->escape($l->lang_code) ?>"><?= $this->escape($l->title) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="wma-refresh-preview">
                <i class="fa fa-refresh"></i> <?= Text::_('COM_WMACOMMUNICATION_EDITOR_REFRESH_PREVIEW') ?>
            </button>
            <div class="wma-editor-topbar-title">
                <label for="wma-form-title"><?= Text::_('COM_WMACOMMUNICATION_FORM_NAME') ?></label>
                <input type="text" id="wma-form-title" value="<?= $this->escape($this->item->title ?? '') ?>" placeholder="<?= Text::_('COM_WMACOMMUNICATION_FORM_TITLE_PLACEHOLDER') ?>">
            </div>
        </div>
    </div>

    <div class="wma-editor-main">

        <div class="wma-editor-sidebar">

            <div class="wma-tab-content active" id="tab-add-field">
                <ul class="wma-field-types">
                    <?php foreach ($this->fieldTypes as $type => $info) : ?>
                    <li class="wma-field-type-item" data-type="<?= $type ?>">
                        <i class="fa <?= $info['icon'] ?>"></i>
                        <span><?= Text::_($info['label']) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="wma-tab-content" id="tab-all-fields">
                <ul class="wma-fields-list" id="wma-fields-list">
                    <?php foreach ($fields as $index => $field) : ?>
                    <li class="wma-field-item" data-index="<?= $index ?>">
                        <span class="wma-field-item-label"><?= $this->escape($field['label'] ?? $field['type']) ?></span>
                        <div class="wma-field-item-actions">
                            <button type="button" class="wma-btn-move-up" data-index="<?= $index ?>" <?= $index === 0 ? 'disabled' : '' ?>>
                                <i class="fa fa-chevron-up"></i>
                            </button>
                            <button type="button" class="wma-btn-move-down" data-index="<?= $index ?>" <?= $index === count($fields) - 1 ? 'disabled' : '' ?>>
                                <i class="fa fa-chevron-down"></i>
                            </button>
                            <button type="button" class="wma-btn-delete" data-index="<?= $index ?>">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="wma-tab-content" id="tab-options">
                <div id="wma-field-options">
                    <p class="wma-no-selection"><?= Text::_('COM_WMACOMMUNICATION_EDITOR_SELECT_FIELD') ?></p>
                </div>
            </div>

            <div class="wma-tab-content" id="tab-sending">
                <div class="wma-sending-options">

                    <div class="wma-field-option">
                        <label><?= Text::_('COM_WMACOMMUNICATION_SENDING_TO') ?></label>
                        <input type="text" id="wma-sending-to" placeholder="email@esempio.com, email2@esempio.com">
                        <small class="wma-msg-hint"><?= Text::_('COM_WMACOMMUNICATION_SENDING_TO_DESC') ?></small>
                    </div>

                    <div class="wma-field-option">
                        <label><?= Text::_('COM_WMACOMMUNICATION_SENDING_CC') ?></label>
                        <input type="text" id="wma-sending-cc" placeholder="email@esempio.com">
                        <small class="wma-msg-hint"><?= Text::_('COM_WMACOMMUNICATION_SENDING_CC_DESC') ?></small>
                    </div>

                    <div class="wma-field-option">
                        <label><?= Text::_('COM_WMACOMMUNICATION_SENDING_CCN') ?></label>
                        <input type="text" id="wma-sending-ccn" placeholder="email@esempio.com">
                        <small class="wma-msg-hint"><?= Text::_('COM_WMACOMMUNICATION_SENDING_CCN_DESC') ?></small>
                    </div>

                    <div class="wma-field-option">
                        <label><?= Text::_('COM_WMACOMMUNICATION_SENDING_REPLY_TO') ?></label>
                        <select id="wma-sending-replyto">
                            <option value=""><?= Text::_('COM_WMACOMMUNICATION_SENDING_REPLY_TO_NONE') ?></option>
                        </select>
                        <small><?= Text::_('COM_WMACOMMUNICATION_SENDING_REPLY_TO_DESC') ?></small>
                    </div>

                    <div class="wma-field-option">
                        <label><?= Text::_('COM_WMACOMMUNICATION_SENDING_OFFICE_TO') ?></label>
                        <select id="wma-sending-officeto">
                            <option value=""><?= Text::_('COM_WMACOMMUNICATION_SENDING_OFFICE_TO_NONE') ?></option>
                        </select>
                        <small><?= Text::_('COM_WMACOMMUNICATION_SENDING_OFFICE_TO_DESC') ?></small>
                    </div>

                    <div class="wma-field-option">
                        <label><?= Text::_('COM_WMACOMMUNICATION_SENDING_SENDER_NAME') ?></label>
                        <input type="text" id="wma-sending-sender-name" placeholder="<?= Text::_('COM_WMACOMMUNICATION_SENDING_SENDER_NAME_PLACEHOLDER') ?>">
                        <small class="wma-msg-hint"><?= Text::_('COM_WMACOMMUNICATION_SENDING_SENDER_NAME_DESC') ?></small>
                    </div>

                </div>
            </div>

            <div class="wma-tab-content" id="tab-messages">
                <div class="wma-messages-block" id="wma-messages-block">

                    <div class="wma-field-option">
                        <label><?= Text::_('COM_WMACOMMUNICATION_MESSAGES_SUCCESS_MSG') ?></label>
                        <textarea id="wma-msg-success" rows="3" placeholder="<?= $this->escape(Text::_('COM_WMACOMMUNICATION_SENDING_SUCCESS_MSG_PLACEHOLDER')) ?>"></textarea>
                        <small class="wma-msg-hint"><?= Text::_('COM_WMACOMMUNICATION_MESSAGES_SUCCESS_MSG_DESC') ?></small>
                    </div>

                    <div class="wma-field-option">
                        <label><?= Text::_('COM_WMACOMMUNICATION_MESSAGES_EMAIL_BODY') ?></label>
                        <div class="wma-msg-toolbar">
                            <button type="button" id="wma-msg-btn-link"><i class="fa fa-link"></i> <?= Text::_('COM_WMACOMMUNICATION_MESSAGES_TOOLBAR_LINK') ?></button>
                            <button type="button" id="wma-msg-btn-image"><i class="fa fa-image"></i> <?= Text::_('COM_WMACOMMUNICATION_MESSAGES_TOOLBAR_IMAGE') ?></button>
                        </div>
                        <textarea id="wma-msg-email-body" rows="8" placeholder="<?= $this->escape(Text::_('COM_WMACOMMUNICATION_SENDING_EMAIL_BODY_PLACEHOLDER')) ?>"></textarea>
                        <small class="wma-msg-hint"><?= Text::_('COM_WMACOMMUNICATION_MESSAGES_EMAIL_BODY_HINT') ?></small>
                    </div>

                </div>
            </div>

        </div>

        <div class="wma-editor-preview">
            <div class="wma-editor-preview-container" id="wma-preview-container">
            </div>
        </div>

    </div>

</div>

<form name="adminForm" id="adminForm" action="<?= Route::_('index.php?option=com_wmacommunication') ?>" method="post">
    <input type="hidden" name="jform[id]" value="<?= $formId ?>">
    <input type="hidden" name="id" value="<?= $formId ?>">
    <input type="hidden" name="jform[title]" id="wma-input-title" value="<?= $this->escape($this->item->title ?? '') ?>">
    <input type="hidden" name="jform[fields]" id="wma-input-fields" value="<?= $this->escape(json_encode($fields)) ?>">
    <input type="hidden" name="jform[state]" value="<?= (int) ($this->item->state ?? 1) ?>">
    <?php
    $settingsData['translations']['_base'] = $baseLang;
    if ($isNew) {
        // I testi predefiniti vengono "congelati" come testo semplice nella lingua
        // base: il frontend deve risolvere solo i segnaposto dei campi ({Nome}, ...).
        if (empty($settingsData['messages_base']['success_msg'])) {
            $settingsData['messages_base']['success_msg'] =
                Text::_('COM_WMACOMMUNICATION_SUCCESS_GREETING') . ' {Nome} '
                . Text::_('COM_WMACOMMUNICATION_SUCCESS_BODY') . ' {email} '
                . Text::_('COM_WMACOMMUNICATION_SUCCESS_PHONE') . ' {Telefono}';
        }
        if (empty($settingsData['messages_base']['email_body'])) {
            $settingsData['messages_base']['email_body'] =
                  '<h2>' . Text::_('COM_WMACOMMUNICATION_EMAIL_FROM_SITE') . '</h2>'
                . '<p>' . Text::_('COM_WMACOMMUNICATION_EMAIL_SENT_BY') . ': {Nome}</p>'
                . '<p>' . Text::_('COM_WMACOMMUNICATION_EMAIL_SENDER') . ': {email}</p>'
                . '<p>' . Text::_('COM_WMACOMMUNICATION_EMAIL_PHONE') . ': {Telefono}</p>'
                . '<p>' . Text::_('COM_WMACOMMUNICATION_EMAIL_SUBJECT') . ': {Oggetto}</p>'
                . '<hr />'
                . '<p>' . Text::_('COM_WMACOMMUNICATION_EMAIL_MESSAGE') . ': {Messaggio}</p>'
                . '<hr />'
                . '<p>' . Text::_('COM_WMACOMMUNICATION_EMAIL_PRIVACY') . ': {Privacy}</p>';
        }
    }
    ?>
    <input type="hidden" name="jform[settings]" id="wma-input-settings" value="<?= $this->escape(json_encode((object)$settingsData)) ?>">
    <input type="hidden" name="task" value="form.save">
    <?= HTMLHelper::_('form.token') ?>
</form>