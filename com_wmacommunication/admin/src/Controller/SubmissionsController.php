<?php
/**
 * @package     Wma.Component.Wmacommunication
 * @subpackage  com_wmacommunication
 *
 * @copyright   Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Wma\Component\Wmacommunication\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class SubmissionsController extends BaseController
{
    private function assertAuthorised(string $action): bool
    {
        if (!$this->app->getIdentity()->authorise($action, 'com_wmacommunication')) {
            $this->app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_wmacommunication&view=submissions', false));

            return false;
        }

        return true;
    }

    public function delete(): void
    {
        $this->checkToken();

        if (!$this->assertAuthorised('core.delete')) {
            return;
        }

        $app  = Factory::getApplication();
        $cids = array_filter(array_map('intval', (array) $app->input->get('cid', [], 'array')));

        if (empty($cids)) {
            $app->enqueueMessage(Text::_('COM_WMACOMMUNICATION_SUBMISSION_DELETE_SELECT_ONE'), 'warning');
        } else {
            $db = $this->getModel('Submissions', 'Administrator')->getDatabase();
            $db->setQuery(
                $db->getQuery(true)
                    ->delete($db->quoteName('#__wmacommunication_submissions'))
                    ->whereIn($db->quoteName('id'), $cids)
            )->execute();

            $app->enqueueMessage(Text::sprintf('COM_WMACOMMUNICATION_SUBMISSION_DELETE_SUCCESS', count($cids)), 'success');
        }

        $this->setRedirect(Route::_('index.php?option=com_wmacommunication&view=submissions', false));
    }

    public function markread(): void
    {
        $this->checkToken();

        if (!$this->assertAuthorised('core.edit.state')) {
            return;
        }

        $app  = Factory::getApplication();
        $cids = array_filter(array_map('intval', (array) $app->input->get('cid', [], 'array')));
        $read = $app->input->getInt('is_read', 1);

        if (!empty($cids)) {
            $db = $this->getModel('Submissions', 'Administrator')->getDatabase();
            $db->setQuery(
                $db->getQuery(true)
                    ->update($db->quoteName('#__wmacommunication_submissions'))
                    ->set($db->quoteName('is_read') . ' = ' . (int) (bool) $read)
                    ->whereIn($db->quoteName('id'), $cids)
            )->execute();
        }

        $this->setRedirect(Route::_('index.php?option=com_wmacommunication&view=submissions', false));
    }

    public function exportcsv(): void
    {
        $this->checkToken();

        if (!$this->assertAuthorised('core.manage')) {
            return;
        }

        $app   = Factory::getApplication();
        $model = $this->getModel('Submissions', 'Administrator');

        // Applica gli stessi filtri correnti della lista (form/lettura/ricerca), senza paginazione.
        // getState() forza populateState() a girare subito, così il list.limit forzato a 0 non
        // viene sovrascritto dalla lettura della richiesta.
        $model->getState();
        $model->setState('list.limit', 0);
        $items = $model->getItems();

        $filename = 'wma_submissions_' . gmdate('Ymd_His') . '.csv';

        $app->allowCache(false);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM per Excel
        fputcsv($out, ['ID', 'Modulo', 'Data invio', 'IP', 'Dati'], ';');

        $db = $model->getDatabase();
        foreach ($items as $item) {
            $row = $db->setQuery(
                $db->getQuery(true)
                    ->select($db->quoteName('data'))
                    ->from($db->quoteName('#__wmacommunication_submissions'))
                    ->where($db->quoteName('id') . ' = ' . (int) $item->id)
            )->loadResult();

            $decoded = json_decode($row ?? '', true) ?: [];
            $pairs   = [];
            foreach ($decoded as $d) {
                $pairs[] = ($d['label'] ?? '') . ': ' . ($d['value'] ?? '');
            }

            fputcsv($out, [
                $item->id,
                $item->form_title,
                $item->created,
                $item->ip,
                implode(' | ', $pairs),
            ], ';');
        }

        fclose($out);
        $app->close();
    }
}
