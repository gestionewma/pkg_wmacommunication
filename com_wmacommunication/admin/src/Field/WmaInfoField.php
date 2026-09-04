<?php
/**
 * @package     Wma.Component.Wmacommunication
 * @subpackage  com_wmacommunication
 *
 * @copyright   Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://www.wma.ovh
 */

namespace Wma\Component\Wmacommunication\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Campo "Informazioni" per il tab Informazioni delle opzioni del componente.
 * Legge versione/autore/copyright dal manifest_cache; usa fallback hardcoded se la lettura fallisce.
 */
class WmaInfoField extends FormField
{
    protected $type = 'WmaInfo';

    public function getInput(): string
    {
        $version      = '2.2.0';
        $creationDate = '04/09/2026';
        $author       = 'Team Developer by WMA Web Maker Agency';
        $email        = 'wmaextension@gmail.com';
        $authorUrl    = 'https://www.wma.ovh';
        $copyright    = 'Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.';

        try {
            $db    = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select($db->quoteName('manifest_cache'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_wmacommunication'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            $row = $db->setQuery($query)->loadObject();

            if ($row && !empty($row->manifest_cache)) {
                $manifest     = json_decode($row->manifest_cache);
                $version      = $manifest->version      ?? $version;
                $creationDate = $manifest->creationDate ?? $creationDate;
                $author       = $manifest->author       ?? $author;
                $email        = $manifest->authorEmail  ?? $email;
                $authorUrl    = $manifest->authorUrl    ?? $authorUrl;
                $copyright    = $manifest->copyright    ?? $copyright;
            }
        } catch (\Throwable $e) {
            // fallback ai valori di default
        }

        $rawUrl     = (string) $authorUrl;
        $isValidUrl = false;
        if ($rawUrl !== '' && filter_var($rawUrl, FILTER_VALIDATE_URL)) {
            $scheme = strtolower((string) parse_url($rawUrl, PHP_URL_SCHEME));
            $isValidUrl = \in_array($scheme, ['http', 'https'], true);
        }

        $rawEmail     = (string) $email;
        $isValidEmail = $rawEmail !== '' && filter_var($rawEmail, FILTER_VALIDATE_EMAIL);

        $version      = htmlspecialchars($version,      ENT_QUOTES, 'UTF-8');
        $creationDate = htmlspecialchars($creationDate, ENT_QUOTES, 'UTF-8');
        $author       = htmlspecialchars($author,       ENT_QUOTES, 'UTF-8');
        $email        = htmlspecialchars($email,        ENT_QUOTES, 'UTF-8');
        $authorUrl    = htmlspecialchars($authorUrl,    ENT_QUOTES, 'UTF-8');
        $copyright    = htmlspecialchars($copyright,    ENT_QUOTES, 'UTF-8');
        $title        = htmlspecialchars(Text::_('COM_WMACOMMUNICATION'), ENT_QUOTES, 'UTF-8');

        $labelVersion   = htmlspecialchars(Text::_('COM_WMACOMMUNICATION_CREDITS_VERSION'), ENT_QUOTES, 'UTF-8');
        $labelDate      = htmlspecialchars(Text::_('COM_WMACOMMUNICATION_CREDITS_DATE'), ENT_QUOTES, 'UTF-8');
        $labelAuthor    = htmlspecialchars(Text::_('COM_WMACOMMUNICATION_CREDITS_AUTHOR'), ENT_QUOTES, 'UTF-8');
        $labelEmail     = htmlspecialchars(Text::_('COM_WMACOMMUNICATION_CREDITS_EMAIL'), ENT_QUOTES, 'UTF-8');
        $labelWebsite   = htmlspecialchars(Text::_('COM_WMACOMMUNICATION_CREDITS_WEBSITE'), ENT_QUOTES, 'UTF-8');
        $labelLicense   = htmlspecialchars(Text::_('COM_WMACOMMUNICATION_CREDITS_LICENSE'), ENT_QUOTES, 'UTF-8');
        $labelCopyright = htmlspecialchars(Text::_('COM_WMACOMMUNICATION_CREDITS_COPYRIGHT'), ENT_QUOTES, 'UTF-8');

        $logoPath = JPATH_ROOT . '/media/com_wmacommunication/images/logo-wma.png';
        $logoUrl  = Uri::root() . 'media/com_wmacommunication/images/logo-wma.png';
        $logoHtml = file_exists($logoPath)
            ? '<img src="' . $logoUrl . '" alt="WMA Web Maker Agency" style="max-width:200px;height:auto;" onerror="this.style.display=\'none\';">'
            : '<div style="width:64px;height:64px;display:flex;align-items:center;justify-content:center;background:#457d54;color:#fff;font-size:1.4rem;font-weight:800;border-radius:8px;letter-spacing:.05em;">WMA</div>';

        $th = 'width:35%;padding:10px 16px;background:#fff;border-bottom:1px solid #dee2e6;border-right:1px solid #dee2e6;font-weight:600;color:#495057;text-align:left;';
        $td = 'padding:10px 16px;background:#fff;border-bottom:1px solid #dee2e6;color:#212529;';

        $emailRow = $isValidEmail
            ? "<tr><th style=\"$th\">$labelEmail</th><td style=\"$td\"><a href=\"mailto:$email\" style=\"color:#457d54;\">$email</a></td></tr>"
            : '';
        $urlRow = $isValidUrl
            ? "<tr><th style=\"$th\">$labelWebsite</th><td style=\"$td\"><a href=\"$authorUrl\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"color:#457d54;\">$authorUrl</a></td></tr>"
            : '';

        return <<<HTML
        <div style="max-width:640px;font-family:inherit;">
            <div style="display:flex;align-items:center;gap:20px;padding:20px 24px;background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px 8px 0 0;border-bottom:none;">
                {$logoHtml}
                <div>
                    <h2 style="margin:0 0 4px;font-size:1.25rem;color:#212529;">{$title}</h2>
                    <p style="margin:0;font-size:.9rem;color:#6c757d;">{$labelVersion} {$version} &mdash; {$creationDate}</p>
                </div>
            </div>
            <table style="width:100%;border-collapse:collapse;border:1px solid #dee2e6;border-radius:0 0 8px 8px;overflow:hidden;font-size:.9rem;">
                <tbody>
                    <tr><th style="{$th}">Component</th><td style="{$td}"><code>com_wmacommunication</code></td></tr>
                    <tr><th style="{$th}">{$labelVersion}</th><td style="{$td}"><strong>{$version}</strong></td></tr>
                    <tr><th style="{$th}">{$labelDate}</th><td style="{$td}">{$creationDate}</td></tr>
                    <tr><th style="{$th}">{$labelAuthor}</th><td style="{$td}">{$author}</td></tr>
                    {$emailRow}
                    {$urlRow}
                    <tr><th style="{$th}">{$labelLicense}</th><td style="{$td}"><span style="display:inline-flex;align-items:center;gap:6px;background:#e8f5e9;color:#2e7d32;border:1px solid #c8e6c9;border-radius:4px;padding:2px 8px;font-size:.8rem;font-weight:600;">GNU GPL v2+</span></td></tr>
                    <tr><th style="width:35%;padding:10px 16px;background:#fff;border-right:1px solid #dee2e6;font-weight:600;color:#495057;text-align:left;">{$labelCopyright}</th><td style="padding:10px 16px;background:#fff;color:#212529;">{$copyright}</td></tr>
                </tbody>
            </table>
        </div>
        HTML;
    }

    public function getLabel(): string
    {
        return '';
    }
}
