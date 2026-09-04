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

HTMLHelper::_('behavior.keepalive');
?>

<div class="container-popup p-3">

    <h3><?= Text::_('COM_WMACOMMUNICATION_IMPORT') ?></h3>
    <p><?= Text::_('COM_WMACOMMUNICATION_TEMPLATE_IMPORT_DESC') ?></p>

    <form action="<?= Route::_('index.php?option=com_wmacommunication&task=templates.doimport') ?>" method="post" enctype="multipart/form-data">

        <div class="mb-3">
            <label for="import_file" class="form-label"><?= Text::_('COM_WMACOMMUNICATION_IMPORT_FILE') ?></label>
            <input type="file" class="form-control" id="import_file" name="import_file" accept=".json" required>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <span class="icon-upload"></span> <?= Text::_('COM_WMACOMMUNICATION_IMPORT_BUTTON') ?>
            </button>
            <button type="button" class="btn btn-secondary" onclick="window.parent.Joomla.Modal.getCurrent().close()">
                <?= Text::_('JCANCEL') ?>
            </button>
        </div>

        <?= HTMLHelper::_('form.token') ?>

    </form>

</div>
