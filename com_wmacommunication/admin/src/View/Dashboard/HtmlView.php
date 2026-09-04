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

namespace Wma\Component\Wmacommunication\Administrator\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class HtmlView extends BaseHtmlView
{
    /** @var array Dati crediti (versione, autore, ecc.) letti dal manifest */
    protected $credits = [];

    /** @var array{path: string, inside_docroot: bool, exists: bool} Info cartella allegati */
    protected $attachments = [];

    public function display($tpl = null): void
    {
        $wa = $this->document->getWebAssetManager();
        $wa->useStyle('com_wmacommunication.admin');
        $wa->useScript('bootstrap.tab');

        $this->credits     = $this->getCredits();
        $this->attachments = $this->getAttachmentsInfo();

        ToolbarHelper::title(Text::_('COM_WMACOMMUNICATION_DASHBOARD'), 'home');

        parent::display($tpl);
    }

    /**
     * Legge versione/autore/copyright dal manifest_cache del componente,
     * con fallback ai valori correnti se la lettura fallisce.
     */
    private function getCredits(): array
    {
        $data = [
            'version'      => '2.1.6',
            'creationDate' => '02/09/2026',
            'author'       => 'Team Developer by WMA Web Maker Agency',
            'authorEmail'  => 'wmaextension@gmail.com',
            'authorUrl'    => 'https://www.wma.ovh',
            'copyright'    => 'Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.',
        ];

        try {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select($db->quoteName('manifest_cache'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_wmacommunication'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            $cache = $db->setQuery($query)->loadResult();

            if ($cache) {
                $manifest = json_decode($cache);

                foreach (['version', 'creationDate', 'author', 'authorEmail', 'authorUrl', 'copyright'] as $key) {
                    if (!empty($manifest->$key)) {
                        $data[$key] = (string) $manifest->$key;
                    }
                }
            }
        } catch (\Throwable $e) {
            // fallback ai valori di default
        }

        return $data;
    }

    /**
     * Percorso reale in cui vengono salvati gli allegati dei form + se è dentro
     * il document root (in tal caso va spostato fuori).
     */
    private function getAttachmentsInfo(): array
    {
        try {
            $helper = '\\Wma\\Component\\Wmacommunication\\Site\\Helper\\AttachmentHelper';
            $path   = $helper::resolvedPath();

            return [
                'path'           => $path,
                'inside_docroot' => $helper::isInsideDocroot($path),
                'exists'         => is_dir($path),
            ];
        } catch (\Throwable $e) {
            return ['path' => '', 'inside_docroot' => false, 'exists' => false];
        }
    }
}