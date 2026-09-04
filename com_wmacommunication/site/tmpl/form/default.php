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
 * @date        30/06/2026
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

$form     = $this->form;
$fields   = $form->fields_decoded ?? [];
$app      = Factory::getApplication();
$formId   = (int) $form->id;
$itemId   = $app->input->getInt('Itemid', 0);

HTMLHelper::_('behavior.keepalive');
?>

<div class="wma-communication-form">

    <?php $title = $this->tr('form.title', (string) $form->title); ?>
    <?php if ($title) : ?>
        <h2 class="wma-cf-title"><?= $this->escape($title) ?></h2>
    <?php endif; ?>

    <?php $description = $this->safeDescription(); ?>
    <?php if ($description !== '') : ?>
        <div class="wma-cf-description"><?= $description ?></div>
    <?php endif; ?>

    <form action="<?= Route::_('index.php?option=com_wmacommunication&task=submit.submit') ?>"
          method="post"
          class="wma-cf-form"
          id="wma-cf-form-<?= $formId ?>"
          enctype="multipart/form-data"
          data-wma-messages="<?= $this->validationMessagesJson() ?>"
          novalidate>

        <div class="wma-cf-fields">
        <?php foreach ($fields as $index => $field) : ?>
            <?= $this->renderField($field, $index) ?>
        <?php endforeach; ?>
        </div>

        <input type="hidden" name="form_id" value="<?= $formId ?>">
        <input type="hidden" name="Itemid" value="<?= $itemId ?>">
        <?= HTMLHelper::_('form.token') ?>

    </form>

</div>