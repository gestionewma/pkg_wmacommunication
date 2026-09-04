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
use Joomla\CMS\Uri\Uri;

$item = $this->item;
?>
<div class="row">
    <div class="col-md-8">
        <table class="table table-striped">
            <tbody>
            <?php foreach ($item->data_decoded as $d) : ?>
                <tr>
                    <td style="width:30%;font-weight:bold;"><?= $this->escape($d['label'] ?? '') ?></td>
                    <td>
                        <?php if (($d['type'] ?? '') === 'fileupload' && !empty($d['value'])) :
                            $parts = explode('|', (string) $d['value'], 2);
                            $name  = $parts[0] ?? '';
                            $token = preg_replace('/[^a-f0-9]/', '', $parts[1] ?? '');
                            if ($token !== '') :
                                $url = rtrim(Uri::root(), '/') . '/index.php?option=com_wmacommunication&task=download.attachment&token=' . $token;
                                ?>
                                <a href="<?= $this->escape($url) ?>" target="_blank"><i class="fa fa-paperclip"></i> <?= $this->escape($name) ?></a>
                            <?php else : ?>
                                <?= $this->escape($name) ?>
                            <?php endif; ?>
                        <?php else : ?>
                            <?= nl2br($this->escape((string) ($d['value'] ?? ''))) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="col-md-4">
        <table class="table table-sm">
            <tr><td><?= Text::_('COM_WMACOMMUNICATION_SUBMISSIONS_COL_FORM') ?></td><td><?= $this->escape($item->form_title) ?></td></tr>
            <tr><td><?= Text::_('COM_WMACOMMUNICATION_SUBMISSIONS_COL_DATE') ?></td><td><?= HTMLHelper::_('date', $item->created, 'd/m/Y H:i') ?></td></tr>
            <tr><td><?= Text::_('COM_WMACOMMUNICATION_SUBMISSIONS_COL_IP') ?></td><td><?= $this->escape($item->ip) ?></td></tr>
            <tr><td><?= Text::_('JGRID_HEADING_ID') ?></td><td><?= (int) $item->id ?></td></tr>
        </table>
    </div>
</div>
