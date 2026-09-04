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

namespace Wma\Component\Wmacommunication\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class FormsController extends AdminController
{
    protected $text_prefix = 'COM_WMACOMMUNICATION';

    /**
     * Colonne realmente presenti nella tabella: qualsiasi altra chiave presente
     * in un JSON importato viene scartata prima della INSERT.
     */
    private const ALLOWED_COLUMNS = [
        'title', 'alias', 'description', 'fields', 'settings',
        'recipient_email', 'email_subject', 'success_message', 'state',
        'created', 'created_by', 'modified', 'modified_by',
        'checked_out', 'checked_out_time',
    ];

    private function filterColumns(array $data): array
    {
        return array_intersect_key($data, array_flip(self::ALLOWED_COLUMNS));
    }

    private function assertAuthorised(string $action): bool
    {
        if (!$this->app->getIdentity()->authorise($action, 'com_wmacommunication')) {
            $this->app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_wmacommunication&view=forms', false));

            return false;
        }

        return true;
    }

    public function getModel($name = 'Form', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function export(): void
    {
        $this->checkToken();

        if (!$this->assertAuthorised('core.manage')) {
            return;
        }

        $app   = Factory::getApplication();
        $cids  = (array) $app->input->get('cid', [], 'array');
        $cids  = array_map('intval', $cids);

        if (empty($cids)) {
            $app->enqueueMessage(Text::_('COM_WMACOMMUNICATION_EXPORT_SELECT_ONE'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_wmacommunication&view=forms', false));
            return;
        }

        // Prende solo il primo selezionato
        $id = $cids[0];

        $db    = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__wmacommunication_forms'))
            ->where($db->quoteName('id') . ' = ' . $id);

        $form = $db->setQuery($query)->loadAssoc();

        if (!$form) {
            $app->enqueueMessage(Text::_('COM_WMACOMMUNICATION_EXPORT_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_wmacommunication&view=forms', false));
            return;
        }

        // Rimuove l'id per permettere reimportazione
        unset($form['id']);
        unset($form['created_by']);
        unset($form['modified_by']);
        unset($form['created']);
        unset($form['modified']);

        $json     = json_encode($form, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'wma_form_' . preg_replace('/[^a-z0-9_]/i', '_', $form['title']) . '.json';

        $app->allowCache(false);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo $json;
        $app->close();
    }

    public function duplicate(): void
    {
        $this->checkToken();

        if (!$this->assertAuthorised('core.create')) {
            return;
        }

        $app  = Factory::getApplication();
        $cids = (array) $app->input->get('cid', [], 'array');
        $cids = array_map('intval', $cids);

        if (empty($cids)) {
            $app->enqueueMessage(Text::_('COM_WMACOMMUNICATION_DUPLICATE_SELECT_ONE'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_wmacommunication&view=forms', false));
            return;
        }

        $db = Factory::getContainer()->get('DatabaseDriver');
        $userId = $app->getIdentity()->id;
        $now    = Factory::getDate()->toSql();

        foreach ($cids as $id) {
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__wmacommunication_forms'))
                ->where($db->quoteName('id') . ' = ' . $id);
            $form = $db->setQuery($query)->loadAssoc();

            if (!$form) {
                continue;
            }

            unset($form['id']);
            $form = $this->filterColumns($form);
            $form['title']       = Text::_('COM_WMACOMMUNICATION_DUPLICATE_PREFIX') . ' ' . $form['title'];
            $form['state']       = 0;
            $form['created']     = $now;
            $form['created_by']  = $userId;
            $form['modified']    = $now;
            $form['modified_by'] = $userId;
            $form['checked_out'] = null;
            $form['checked_out_time'] = null;

            $columns = array_keys($form);
            $values  = array_map(fn($v) => is_null($v) ? 'NULL' : $db->quote($v), array_values($form));

            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__wmacommunication_forms'))
                ->columns(array_map(fn($c) => $db->quoteName($c), $columns))
                ->values(implode(',', $values));
            $db->setQuery($query)->execute();
        }

        $app->enqueueMessage(Text::_('COM_WMACOMMUNICATION_DUPLICATE_SUCCESS'), 'success');
        $this->setRedirect(Route::_('index.php?option=com_wmacommunication&view=forms', false));
    }

    public function importsamples(): void
    {
        $this->checkToken();

        if (!$this->assertAuthorised('core.create')) {
            return;
        }

        $app  = Factory::getApplication();
        $db   = Factory::getContainer()->get('DatabaseDriver');
        $now  = Factory::getDate()->toSql();
        $userId = $app->getIdentity()->id;

        $samplesDir = JPATH_ADMINISTRATOR . '/components/com_wmacommunication/forms/samples';

        if (!is_dir($samplesDir)) {
            $app->enqueueMessage(Text::_('COM_WMACOMMUNICATION_IMPORTSAMPLES_DIR_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_wmacommunication&view=forms', false));
            return;
        }

        $files = glob($samplesDir . '/*.json');
        $total = count($files);
        $imported = 0;
        $skipped = 0;

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data    = json_decode($content, true);

            if (!$data || !isset($data['title'])) {
                continue;
            }

            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__wmacommunication_forms'))
                ->where($db->quoteName('title') . ' = ' . $db->quote($data['title']));
            $exists = (int) $db->setQuery($query)->loadResult();

            if ($exists) {
                $skipped++;
                continue;
            }

            unset($data['id']);
            $data = $this->filterColumns($data);
            $data['state']       = 0;
            $data['created']     = $now;
            $data['created_by']  = $userId;
            $data['modified']    = $now;
            $data['modified_by'] = $userId;
            $data['checked_out'] = null;
            $data['checked_out_time'] = null;

            $columns = array_keys($data);
            $values  = array_map(fn($v) => is_null($v) ? 'NULL' : $db->quote($v), array_values($data));

            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__wmacommunication_forms'))
                ->columns(array_map(fn($c) => $db->quoteName($c), $columns))
                ->values(implode(',', $values));
            $db->setQuery($query)->execute();
            $imported++;
        }

        if ($total === 0) {
            $app->enqueueMessage(Text::_('COM_WMACOMMUNICATION_IMPORTSAMPLES_NONE'), 'info');
        } else {
            $app->enqueueMessage(
                Text::sprintf('COM_WMACOMMUNICATION_IMPORTSAMPLES_SUMMARY', $imported, $total, $skipped),
                $skipped > 0 ? 'warning' : 'success'
            );

            if ($skipped > 0) {
                $app->enqueueMessage(Text::_('COM_WMACOMMUNICATION_IMPORTSAMPLES_RENAME_WARNING'), 'warning');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_wmacommunication&view=forms', false));
    }

    public function importform(): void
    {
        // Mostra il template per il popup di importazione
        $app = Factory::getApplication();
        $app->input->set('view', 'forms');
        $app->input->set('layout', 'import');

        parent::display();
    }

public function doimport(): void
{
    $this->checkToken();

    if (!$this->assertAuthorised('core.create')) {
        return;
    }

    $app  = Factory::getApplication();
    $file = $app->input->files->get('import_file', [], 'array');

    if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $app->enqueueMessage(Text::_('COM_WMACOMMUNICATION_IMPORT_FILE_ERROR'), 'error');
    } else {
        $content = file_get_contents($file['tmp_name']);
        $data    = json_decode($content, true);

        if (!$data || !isset($data['title'])) {
            $app->enqueueMessage(Text::_('COM_WMACOMMUNICATION_IMPORT_INVALID_FILE'), 'error');
        } else {
            $db = Factory::getContainer()->get('DatabaseDriver');

            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__wmacommunication_forms'))
                ->where($db->quoteName('title') . ' = ' . $db->quote($data['title']));

            $exists = (int) $db->setQuery($query)->loadResult();

            if ($exists) {
                $data['title'] = 'Import - ' . $data['title'];
            }

            $data = $this->filterColumns($data);
            $data['created']     = Factory::getDate()->toSql();
            $data['created_by']  = Factory::getApplication()->getIdentity()->id;
            $data['modified']    = $data['created'];
            $data['modified_by'] = $data['created_by'];
            $data['state']       = 0;
            $data['checked_out'] = null;
            $data['checked_out_time'] = null;

            $columns = array_keys($data);
            $values  = array_map(fn($v) => is_null($v) ? 'NULL' : $db->quote($v), array_values($data));

            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__wmacommunication_forms'))
                ->columns(array_map(fn($c) => $db->quoteName($c), $columns))
                ->values(implode(',', $values));

            $db->setQuery($query)->execute();

            $app->enqueueMessage(Text::_('COM_WMACOMMUNICATION_IMPORT_SUCCESS'), 'success');
        }
    }

    // Chiude il popup e ricarica la lista
    echo '<script>
        window.parent.location.href = "' . Route::_('index.php?option=com_wmacommunication&view=forms', false) . '";
        window.parent.Joomla.Modal.getCurrent().close();
    </script>';
    $app->close();
} 

}