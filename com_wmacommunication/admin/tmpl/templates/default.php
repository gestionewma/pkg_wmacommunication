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
?>
<form action="<?= Route::_('index.php?option=com_wmacommunication&view=templates') ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped" id="templateList">
                <thead>
                    <tr>
                        <th class="w-1 text-center"><?= HTMLHelper::_('grid.checkall') ?></th>
                        <th><?= Text::_('JGLOBAL_TITLE') ?></th>
                        <th class="w-10 text-center"><?= Text::_('JSTATUS') ?></th>
                        <th class="w-5"><?= Text::_('JGRID_HEADING_ID') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($this->items as $i => $item) : ?>
                    <tr>
                        <td class="text-center"><?= HTMLHelper::_('grid.id', $i, $item->id) ?></td>
                        <td>
                            <a href="<?= Route::_('index.php?option=com_wmacommunication&task=template.edit&id=' . $item->id) ?>">
                                <?= $this->escape($item->title) ?>
                            </a>
                        </td>
                        <td class="text-center"><?= HTMLHelper::_('jgrid.published', $item->state, $i, 'templates.') ?></td>
                        <td><?= $item->id ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?= $this->pagination->getListFooter() ?>
        </div>
    </div>
    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <?= HTMLHelper::_('form.token') ?>
</form>
