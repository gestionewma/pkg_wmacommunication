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

$user      = $this->getCurrentUser();
$userId    = $user->id;
$canCheckin = $user->authorise('core.admin', 'com_wmacommunication');

?>
<form action="<?= Route::_('index.php?option=com_wmacommunication&view=forms') ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped" id="formList">
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
                            <?php if ($item->checked_out) : ?>
                                <?= HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'forms.', $canCheckin || (int) $item->checked_out === (int) $userId) ?>
                            <?php endif; ?>
                            <a href="<?= Route::_('index.php?option=com_wmacommunication&task=form.edit&id=' . $item->id) ?>">
                                <?= $this->escape($item->title) ?>
                            </a>
                        </td>
                        <td class="text-center"><?= HTMLHelper::_('jgrid.published', $item->state, $i, 'forms.') ?></td>
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