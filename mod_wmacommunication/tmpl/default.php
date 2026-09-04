<?php
/**
 * @package     Wma.Module.Wmacommunication
 * @subpackage  mod_wmacommunication
 *
 * @copyright   Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://www.wma.ovh
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

/** @var \Joomla\Registry\Registry $params */
/** @var object $module */
/** @var \stdClass|null $wmaForm */
/** @var \Wma\Component\Wmacommunication\Site\View\Form\HtmlView|null $wmaView */
/** @var int $wmaItemid */

if (empty($wmaForm) || empty($wmaView)) {
    return;
}

$fields      = $wmaForm->fields_decoded ?? [];
$formId      = (int) $wmaForm->id;
$moduleId    = (int) $module->id;
$description = $wmaView->safeDescription();
?>
<div class="wma-cf-module wma-cf-module-<?php echo $moduleId; ?>">

    <?php if ($description !== '') : ?>
        <div class="wma-cf-description"><?php echo $description; ?></div>
    <?php endif; ?>

    <form action="<?php echo Route::_('index.php?option=com_wmacommunication&task=submit.submit'); ?>"
          method="post"
          class="wma-cf-form"
          id="wma-cf-form-<?php echo $formId; ?>-<?php echo $moduleId; ?>"
          enctype="multipart/form-data"
          data-wma-messages="<?php echo $wmaView->validationMessagesJson(); ?>"
          novalidate>

        <div class="wma-cf-fields">
            <?php foreach ($fields as $index => $field) : ?>
                <?php echo $wmaView->renderField($field, $index); ?>
            <?php endforeach; ?>
        </div>

        <input type="hidden" name="form_id" value="<?php echo $formId; ?>">
        <input type="hidden" name="Itemid" value="<?php echo $wmaItemid; ?>">
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
