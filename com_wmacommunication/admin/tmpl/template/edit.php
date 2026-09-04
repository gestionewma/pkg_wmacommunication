<?php
/**
 * @package     Wma.Component.Wmacommunication
 * @subpackage  com_wmacommunication
 *
 * @copyright   Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$isNew = empty($this->item->id);
?>
<form action="<?= Route::_('index.php?option=com_wmacommunication') ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-8">
            <?php foreach ($this->form->getFieldset('general') as $field) : ?>
                <?php if ($field->hidden) : ?>
                    <?= $field->input ?>
                <?php else : ?>
                    <div class="mb-3">
                        <?= $field->label ?>
                        <?= $field->input ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <div class="mb-3">
                <button type="button" class="btn btn-secondary btn-sm" id="wma-template-preview-btn">
                    <?= Text::_('COM_WMACOMMUNICATION_TEMPLATE_PREVIEW') ?>
                </button>
            </div>
            <iframe id="wma-template-preview-frame" title="<?= Text::_('COM_WMACOMMUNICATION_TEMPLATE_PREVIEW') ?>"
                    style="width:100%;min-height:300px;border:1px solid #ced4da;border-radius:4px;background:#fff;" hidden></iframe>
        </div>
    </div>

    <input type="hidden" name="id" value="<?= (int) $this->item->id ?>">
    <input type="hidden" name="task" value="">
    <?= HTMLHelper::_('form.token') ?>
</form>

<script>
(function () {
    // Precompila da "Salva come template" nel tab Messaggi dell'editor del form
    // (stesso browser, sessionStorage — vedi editor.js saveAsTemplate()).
    var isNew = <?= $isNew ? 'true' : 'false' ?>;
    var titleEl = document.getElementById('jform_title');
    var bodyEl  = document.getElementById('jform_body');

    if (isNew && titleEl && bodyEl) {
        try {
            var raw = sessionStorage.getItem('wma_template_prefill');
            if (raw) {
                sessionStorage.removeItem('wma_template_prefill');
                var data = JSON.parse(raw);
                if (data.title && !titleEl.value) titleEl.value = data.title;
                if (data.body && !bodyEl.value) bodyEl.value = data.body;
            }
        } catch (e) { /* ignora */ }
    }

    var btn   = document.getElementById('wma-template-preview-btn');
    var frame = document.getElementById('wma-template-preview-frame');

    if (btn && frame && bodyEl) {
        var update = function () { frame.srcdoc = bodyEl.value || ''; };
        btn.addEventListener('click', function () {
            frame.hidden = !frame.hidden;
            if (!frame.hidden) update();
        });
        bodyEl.addEventListener('input', function () {
            if (!frame.hidden) update();
        });
    }
})();
</script>
