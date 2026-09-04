<?php
/**
 * @package     Wma.Module.Wmacommunication
 * @subpackage  mod_wmacommunication
 *
 * @copyright   Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://www.wma.ovh
 */

namespace Wma\Module\Wmacommunication\Site\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

/**
 * Campo "Informazioni" per il form parametri di mod_wmacommunication.
 * Legge versione/autore/copyright dal manifest_cache; fallback hardcoded se la lettura fallisce.
 */
class WmaInfoField extends FormField
{
    protected $type = 'WmaInfo';

    public function getInput(): string
    {
        $version      = '2.1.6';
        $creationDate = '02/09/2026';
        $author       = 'Team Developer by WMA Web Maker Agency';
        $email        = 'wmaextension@gmail.com';
        $authorUrl    = 'https://www.wma.ovh';
        $copyright    = 'Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.';

        try {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select($db->quoteName('manifest_cache'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('mod_wmacommunication'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('module'));
            $cache = $db->setQuery($query)->loadResult();

            if ($cache) {
                $manifest     = json_decode($cache);
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

        $urlOk  = filter_var((string) $authorUrl, FILTER_VALIDATE_URL)
            && \in_array(strtolower((string) parse_url((string) $authorUrl, PHP_URL_SCHEME)), ['http', 'https'], true);
        $mailOk = (bool) filter_var((string) $email, FILTER_VALIDATE_EMAIL);

        $version      = htmlspecialchars((string) $version,      ENT_QUOTES, 'UTF-8');
        $creationDate = htmlspecialchars((string) $creationDate, ENT_QUOTES, 'UTF-8');
        $author       = htmlspecialchars((string) $author,       ENT_QUOTES, 'UTF-8');
        $email        = htmlspecialchars((string) $email,        ENT_QUOTES, 'UTF-8');
        $authorUrl    = htmlspecialchars((string) $authorUrl,    ENT_QUOTES, 'UTF-8');
        $copyright    = htmlspecialchars((string) $copyright,    ENT_QUOTES, 'UTF-8');
        $title        = htmlspecialchars(Text::_('MOD_WMACOMMUNICATION'), ENT_QUOTES, 'UTF-8');

        $lVer  = htmlspecialchars(Text::_('MOD_WMACOMMUNICATION_CREDITS_VERSION'), ENT_QUOTES, 'UTF-8');
        $lAut  = htmlspecialchars(Text::_('MOD_WMACOMMUNICATION_CREDITS_AUTHOR'), ENT_QUOTES, 'UTF-8');
        $lMail = htmlspecialchars(Text::_('MOD_WMACOMMUNICATION_CREDITS_EMAIL'), ENT_QUOTES, 'UTF-8');
        $lWeb  = htmlspecialchars(Text::_('MOD_WMACOMMUNICATION_CREDITS_WEBSITE'), ENT_QUOTES, 'UTF-8');
        $lLic  = htmlspecialchars(Text::_('MOD_WMACOMMUNICATION_CREDITS_LICENSE'), ENT_QUOTES, 'UTF-8');
        $lCop  = htmlspecialchars(Text::_('MOD_WMACOMMUNICATION_CREDITS_COPYRIGHT'), ENT_QUOTES, 'UTF-8');

        $logoPath = JPATH_ROOT . '/media/com_wmacommunication/images/logo-wma.png';
        $logoUrl  = Uri::root() . 'media/com_wmacommunication/images/logo-wma.png';
        $logoHtml = file_exists($logoPath)
            ? '<img src="' . $logoUrl . '" alt="WMA Web Maker Agency" style="max-width:200px;height:auto;" onerror="this.style.display=\'none\';">'
            : '<div style="width:64px;height:64px;display:flex;align-items:center;justify-content:center;background:#457d54;color:#fff;font-size:1.4rem;font-weight:800;border-radius:8px;">WMA</div>';

        $th = 'width:35%;padding:10px 16px;background:#fff;border-bottom:1px solid #dee2e6;border-right:1px solid #dee2e6;font-weight:600;color:#495057;text-align:left;';
        $td = 'padding:10px 16px;background:#fff;border-bottom:1px solid #dee2e6;color:#212529;';

        $mailRow = $mailOk ? "<tr><th style=\"$th\">$lMail</th><td style=\"$td\"><a href=\"mailto:$email\" style=\"color:#457d54;\">$email</a></td></tr>" : '';
        $urlRow  = $urlOk  ? "<tr><th style=\"$th\">$lWeb</th><td style=\"$td\"><a href=\"$authorUrl\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"color:#457d54;\">$authorUrl</a></td></tr>" : '';

        return <<<HTML
        <div style="max-width:640px;font-family:inherit;">
            <div style="display:flex;align-items:center;gap:20px;padding:20px 24px;background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px 8px 0 0;border-bottom:none;">
                {$logoHtml}
                <div>
                    <h2 style="margin:0 0 4px;font-size:1.25rem;color:#212529;">{$title}</h2>
                    <p style="margin:0;font-size:.9rem;color:#6c757d;">{$lVer} {$version} &mdash; {$creationDate}</p>
                </div>
            </div>
            <table style="width:100%;border-collapse:collapse;border:1px solid #dee2e6;border-radius:0 0 8px 8px;overflow:hidden;font-size:.9rem;">
                <tbody>
                    <tr><th style="{$th}">Module</th><td style="{$td}"><code>mod_wmacommunication</code></td></tr>
                    <tr><th style="{$th}">{$lVer}</th><td style="{$td}"><strong>{$version}</strong></td></tr>
                    <tr><th style="{$th}">{$lAut}</th><td style="{$td}">{$author}</td></tr>
                    {$mailRow}
                    {$urlRow}
                    <tr><th style="{$th}">{$lLic}</th><td style="{$td}">GNU GPL v2+</td></tr>
                    <tr><th style="width:35%;padding:10px 16px;background:#fff;border-right:1px solid #dee2e6;font-weight:600;color:#495057;text-align:left;">{$lCop}</th><td style="padding:10px 16px;background:#fff;color:#212529;">{$copyright}</td></tr>
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
