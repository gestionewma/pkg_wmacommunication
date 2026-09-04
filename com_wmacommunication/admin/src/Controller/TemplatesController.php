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

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class TemplatesController extends AdminController
{
    protected $text_prefix = 'COM_WMACOMMUNICATION_TEMPLATE';

    private const ALLOWED_COLUMNS = ['title', 'body', 'state', 'created', 'created_by', 'modified', 'modified_by'];

    private function filterColumns(array $data): array
    {
        return array_intersect_key($data, array_flip(self::ALLOWED_COLUMNS));
    }

    private function assertAuthorised(string $action): bool
    {
        if (!$this->app->getIdentity()->authorise($action, 'com_wmacommunication')) {
            $this->app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_wmacommunication&view=templates', false));

            return false;
        }

        return true;
    }

    public function getModel($name = 'Template', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function export(): void
    {
        $this->checkToken();

        if (!$this->assertAuthorised('core.manage')) {
            return;
        }

        $app  = Factory::getApplication();
        $cids = array_map('intval', (array) $app->input->get('cid', [], 'array'));

        if (empty($cids)) {
            $app->enqueueMessage(Text::_('COM_WMACOMMUNICATION_TEMPLATE_EXPORT_SELECT_ONE'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_wmacommunication&view=templates', false));
            return;
        }

        $id = $cids[0];
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select($db->quoteName(['title', 'body']))
            ->from($db->quoteName('#__wmacommunication_templates'))
            ->where($db->quoteName('id') . ' = ' . $id);
        $template = $db->setQuery($query)->loadAssoc();

        if (!$template) {
            $app->enqueueMessage(Text::_('COM_WMACOMMUNICATION_TEMPLATE_EXPORT_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_wmacommunication&view=templates', false));
            return;
        }

        $json     = json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'wma_template_' . preg_replace('/[^a-z0-9_]/i', '_', $template['title']) . '.json';

        $app->allowCache(false);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo $json;
        $app->close();
    }

    public function importform(): void
    {
        $app = Factory::getApplication();
        $app->input->set('view', 'templates');
        $app->input->set('layout', 'import');

        parent::display();
    }

    public function installsamples(): void
    {
        $this->checkToken();

        if (!$this->assertAuthorised('core.create')) {
            return;
        }

        $app    = Factory::getApplication();
        $db     = Factory::getContainer()->get('DatabaseDriver');
        $now    = Factory::getDate()->toSql();
        $userId = $app->getIdentity()->id;

        $samplesDir = JPATH_ADMINISTRATOR . '/components/com_wmacommunication/msgtemplates/samples';

        if (!is_dir($samplesDir)) {
            $app->enqueueMessage(Text::_('COM_WMACOMMUNICATION_TEMPLATE_IMPORTSAMPLES_DIR_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_wmacommunication&view=templates', false));
            return;
        }

        $files    = glob($samplesDir . '/*.json');
        $total    = count($files);
        $imported = 0;
        $skipped  = 0;

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);

            if (!$data || !isset($data['title'])) {
                continue;
            }

            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__wmacommunication_templates'))
                ->where($db->quoteName('title') . ' = ' . $db->quote($data['title']));

            if ((int) $db->setQuery($query)->loadResult()) {
                $skipped++;
                continue;
            }

            unset($data['id']);
            $data = $this->filterColumns($data);
            $data['state']       = 1;
            $data['created']     = $now;
            $data['created_by']  = $userId;
            $data['modified']    = $now;
            $data['modified_by'] = $userId;

            $columns = array_keys($data);
            $values  = array_map(fn($v) => is_null($v) ? 'NULL' : $db->quote($v), array_values($data));

            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__wmacommunication_templates'))
                ->columns(array_map(fn($c) => $db->quoteName($c), $columns))
                ->values(implode(',', $values));
            $db->setQuery($query)->execute();
            $imported++;
        }

        if ($total === 0) {
            $app->enqueueMessage(Text::_('COM_WMACOMMUNICATION_TEMPLATE_IMPORTSAMPLES_NONE'), 'info');
        } else {
            $app->enqueueMessage(
                Text::sprintf('COM_WMACOMMUNICATION_TEMPLATE_IMPORTSAMPLES_SUMMARY', $imported, $total, $skipped),
                $skipped > 0 ? 'warning' : 'success'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_wmacommunication&view=templates', false));
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
            $app->enqueueMessage(Text::_('COM_WMACOMMUNICATION_TEMPLATE_IMPORT_FILE_ERROR'), 'error');
        } else {
            $data = json_decode(file_get_contents($file['tmp_name']), true);

            if (!$data || !isset($data['title'])) {
                $app->enqueueMessage(Text::_('COM_WMACOMMUNICATION_TEMPLATE_IMPORT_INVALID_FILE'), 'error');
            } else {
                $db = Factory::getContainer()->get('DatabaseDriver');

                $query = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($db->quoteName('#__wmacommunication_templates'))
                    ->where($db->quoteName('title') . ' = ' . $db->quote($data['title']));

                if ((int) $db->setQuery($query)->loadResult()) {
                    $data['title'] = 'Import - ' . $data['title'];
                }

                unset($data['id']);
                $data = $this->filterColumns($data);
                $data['created']     = Factory::getDate()->toSql();
                $data['created_by']  = $app->getIdentity()->id;
                $data['modified']    = $data['created'];
                $data['modified_by'] = $data['created_by'];
                $data['state']       = 0;

                $columns = array_keys($data);
                $values  = array_map(fn($v) => is_null($v) ? 'NULL' : $db->quote($v), array_values($data));

                $query = $db->getQuery(true)
                    ->insert($db->quoteName('#__wmacommunication_templates'))
                    ->columns(array_map(fn($c) => $db->quoteName($c), $columns))
                    ->values(implode(',', $values));
                $db->setQuery($query)->execute();

                $app->enqueueMessage(Text::_('COM_WMACOMMUNICATION_TEMPLATE_IMPORT_SUCCESS'), 'success');
            }
        }

        echo '<script>
            window.parent.location.href = "' . Route::_('index.php?option=com_wmacommunication&view=templates', false) . '";
            window.parent.Joomla.Modal.getCurrent().close();
        </script>';
        $app->close();
    }
}
