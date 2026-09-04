<?php
/**
 * @package     Wma.Component.Wmacommunication
 * @subpackage  com_wmacommunication
 *
 * @copyright   Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://www.wma.ovh
 */

namespace Wma\Component\Wmacommunication\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\ParameterType;
use Wma\Component\Wmacommunication\Site\Helper\AttachmentHelper;

/**
 * Consegna gli allegati dei form.
 *
 * L'accesso è governato dal token (64 hex, 256 bit) presente nel link inviato per
 * email: nessun login richiesto perché il destinatario del form di norma non è un
 * utente Joomla. Il file sta fuori dal web root e viene servito solo da qui, come
 * download forzato, con eventuale scadenza (retention).
 */
class DownloadController extends BaseController
{
    public function attachment(): void
    {
        $app   = Factory::getApplication();
        $token = (string) $app->input->getString('token', '');

        if (!preg_match('/^[a-f0-9]{32,64}$/', $token)) {
            throw new \RuntimeException(Text::_('COM_WMACOMMUNICATION_DOWNLOAD_NOT_FOUND'), 404);
        }

        $db  = Factory::getContainer()->get('DatabaseDriver');
        $row = $db->setQuery(
            $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__wmacommunication_uploads'))
                ->where($db->quoteName('token') . ' = :token')
                ->bind(':token', $token, ParameterType::STRING)
        )->loadObject();

        if (!$row) {
            throw new \RuntimeException(Text::_('COM_WMACOMMUNICATION_DOWNLOAD_NOT_FOUND'), 404);
        }

        // Scadenza (retention)
        $days = AttachmentHelper::retentionDays();
        if ($days > 0 && (time() - strtotime($row->created)) > $days * 86400) {
            if ($path = AttachmentHelper::pathFor($row)) {
                @unlink($path);
            }
            $db->setQuery(
                $db->getQuery(true)
                    ->delete($db->quoteName('#__wmacommunication_uploads'))
                    ->where($db->quoteName('id') . ' = ' . (int) $row->id)
            )->execute();

            throw new \RuntimeException(Text::_('COM_WMACOMMUNICATION_DOWNLOAD_EXPIRED'), 410);
        }

        $path = AttachmentHelper::pathFor($row);
        if ($path === null) {
            throw new \RuntimeException(Text::_('COM_WMACOMMUNICATION_DOWNLOAD_NOT_FOUND'), 404);
        }

        // Contatore (best effort)
        try {
            $db->setQuery(
                'UPDATE ' . $db->quoteName('#__wmacommunication_uploads')
                . ' SET ' . $db->quoteName('downloads') . ' = ' . $db->quoteName('downloads') . ' + 1, '
                . $db->quoteName('last_download') . ' = ' . $db->quote(Factory::getDate()->toSql())
                . ' WHERE ' . $db->quoteName('id') . ' = ' . (int) $row->id
            )->execute();
        } catch (\Throwable $e) {
        }

        $downloadName = preg_replace('/[\r\n"]+/', '', (string) $row->original_name) ?: 'download';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $app->allowCache(false);
        $app->setHeader('Content-Type', 'application/octet-stream', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="' . $downloadName . '"', true);
        $app->setHeader('Content-Length', (string) filesize($path), true);
        $app->setHeader('X-Content-Type-Options', 'nosniff', true);
        $app->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate', true);
        $app->sendHeaders();

        readfile($path);

        $app->close();
    }
}
