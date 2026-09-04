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

$formId  = (int) $this->state->get('filter.form_id', 0);
$isRead  = $this->state->get('filter.is_read', '');
$search  = $this->state->get('filter.search', '');
$colLbl  = $this->columnLabels;
// Colonne reali solo se filtrato su un form e almeno un campo è assegnato
$useCols = $formId > 0 && ($colLbl['1'] !== '' || $colLbl['2'] !== '');
?>
<form action="<?= Route::_('index.php?option=com_wmacommunication&view=submissions') ?>" method="post" name="adminForm" id="adminForm">
    <div class="row mb-3">
        <div class="col-md-3">
            <select name="filter_form_id" class="form-select" onchange="this.form.submit()">
                <option value="0"><?= Text::_('COM_WMACOMMUNICATION_SUBMISSIONS_FILTER_ALL_FORMS') ?></option>
                <?php foreach ($this->forms as $f) : ?>
                    <option value="<?= (int) $f->form_id ?>" <?= $formId === (int) $f->form_id ? 'selected' : '' ?>>
                        <?= $this->escape($f->form_title) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="filter_is_read" class="form-select" onchange="this.form.submit()">
                <option value=""><?= Text::_('COM_WMACOMMUNICATION_SUBMISSIONS_FILTER_ALL_STATES') ?></option>
                <option value="0" <?= $isRead === '0' ? 'selected' : '' ?>><?= Text::_('COM_WMACOMMUNICATION_SUBMISSIONS_FILTER_UNREAD') ?></option>
                <option value="1" <?= $isRead === '1' ? 'selected' : '' ?>><?= Text::_('COM_WMACOMMUNICATION_SUBMISSIONS_FILTER_READ') ?></option>
            </select>
        </div>
        <div class="col-md-4">
            <input type="text" name="filter_search" class="form-control" value="<?= $this->escape($search) ?>" placeholder="<?= Text::_('JSEARCH_FILTER') ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary w-100"><?= Text::_('JSEARCH_FILTER_SUBMIT') ?></button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped" id="submissionList">
                <thead>
                    <tr>
                        <th class="w-1 text-center"><?= HTMLHelper::_('grid.checkall') ?></th>
                        <th class="w-1 text-center"></th>
                        <th><?= Text::_('COM_WMACOMMUNICATION_SUBMISSIONS_COL_FORM') ?></th>
                        <?php if ($useCols) : ?>
                            <th><?= $colLbl['1'] !== '' ? $this->escape($colLbl['1']) : Text::_('COM_WMACOMMUNICATION_SUBMISSIONS_COL_1') ?></th>
                            <th><?= $colLbl['2'] !== '' ? $this->escape($colLbl['2']) : Text::_('COM_WMACOMMUNICATION_SUBMISSIONS_COL_2') ?></th>
                        <?php else : ?>
                            <th><?= Text::_('COM_WMACOMMUNICATION_SUBMISSIONS_COL_SUMMARY') ?></th>
                        <?php endif; ?>
                        <th class="w-15"><?= Text::_('COM_WMACOMMUNICATION_SUBMISSIONS_COL_DATE') ?></th>
                        <th class="w-10"><?= Text::_('COM_WMACOMMUNICATION_SUBMISSIONS_COL_IP') ?></th>
                        <th class="w-5"><?= Text::_('JGRID_HEADING_ID') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($this->items)) : ?>
                    <tr>
                        <td colspan="<?= $useCols ? 8 : 7 ?>" class="text-center"><?= Text::_('COM_WMACOMMUNICATION_SUBMISSIONS_NONE') ?></td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($this->items as $i => $item) : ?>
                    <tr class="<?= $item->is_read ? '' : 'fw-bold' ?>">
                        <td class="text-center"><?= HTMLHelper::_('grid.id', $i, $item->id) ?></td>
                        <td class="text-center">
                            <?php if (!$item->is_read) : ?>
                                <span class="badge bg-info" title="<?= Text::_('COM_WMACOMMUNICATION_SUBMISSIONS_FILTER_UNREAD') ?>">●</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= Route::_('index.php?option=com_wmacommunication&view=submission&id=' . $item->id) ?>">
                                <?= $this->escape($item->form_title) ?>
                            </a>
                        </td>
                        <?php if ($useCols) : ?>
                            <td><?= $this->escape($item->col1_value) ?></td>
                            <td><?= $this->escape($item->col2_value) ?></td>
                        <?php else : ?>
                            <td><?= $this->escape($item->summary) ?></td>
                        <?php endif; ?>
                        <td><?= HTMLHelper::_('date', $item->created, 'd/m/Y H:i') ?></td>
                        <td><?= $this->escape($item->ip) ?></td>
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
