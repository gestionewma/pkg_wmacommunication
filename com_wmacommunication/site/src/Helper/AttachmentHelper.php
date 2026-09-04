<?php
/**
 * @package     Wma.Component.Wmacommunication
 * @subpackage  com_wmacommunication
 *
 * @copyright   Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://www.wma.ovh
 */

namespace Wma\Component\Wmacommunication\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;

/**
 * Gestione dello storage privato degli allegati dei form.
 *
 * I file NON stanno nel web root: la cartella base è, in ordine di preferenza,
 * quella impostata nelle Opzioni, poi `<root>/../wmacommunication-uploads`
 * (fuori dal document root), infine `<root>/wmacommunication-uploads` con un
 * `.htaccess`/`web.config` "deny all" come rete di sicurezza.
 */
abstract class AttachmentHelper
{
    /**
     * Estensioni ammesse di default se l'admin non compila "Tipi di file".
     * Deve restare allineata all'hint mostrato in frontend.
     */
    public static function defaultAllowedTypes(): array
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'odt', 'txt', 'csv', 'zip'];
    }

    /**
     * Estensioni SEMPRE vietate, a prescindere dalla configurazione.
     */
    public static function blockedTypes(): array
    {
        return [
            'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phps', 'phar',
            'inc', 'pl', 'py', 'cgi', 'sh', 'bash', 'asp', 'aspx', 'jsp', 'jspx', 'exe',
            'com', 'bat', 'cmd', 'msi', 'dll', 'so', 'htaccess', 'htpasswd',
            'shtml', 'phtm', 'svg', 'svgz', 'xml', 'xhtml', 'html', 'htm',
        ];
    }

    public static function retentionDays(): int
    {
        return (int) ComponentHelper::getParams('com_wmacommunication')->get('attachments_retention', 90);
    }

    /**
     * Calcola il percorso della cartella base SENZA crearla (per la sola lettura,
     * es. dashboard). Ordine: Opzione → fuori dal web root → fallback nel web root.
     */
    public static function resolvedPath(): string
    {
        $configured = trim((string) ComponentHelper::getParams('com_wmacommunication')->get('attachments_path', ''));

        if ($configured !== '') {
            return rtrim(str_replace('\\', '/', $configured), '/');
        }

        $outside = str_replace('\\', '/', \dirname(JPATH_ROOT)) . '/wmacommunication-uploads';

        if (is_dir($outside) || @mkdir($outside, 0755, true)) {
            return $outside;
        }

        return str_replace('\\', '/', JPATH_ROOT) . '/wmacommunication-uploads';
    }

    /**
     * Il percorso indicato sta dentro il document root?
     */
    public static function isInsideDocroot(string $path): bool
    {
        $root = str_replace('\\', '/', realpath(JPATH_ROOT) ?: JPATH_ROOT);
        $p    = str_replace('\\', '/', $path);

        return strpos($p . '/', $root . '/') === 0;
    }

    /**
     * Cartella base degli allegati (creata e "blindata" se necessario).
     */
    public static function baseDir(): string
    {
        $dir = self::resolvedPath();

        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        self::harden($dir);

        return $dir;
    }

    /**
     * Scrive nella cartella i file che negano listing ed esecuzione/accesso via web.
     */
    public static function harden(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $htaccess = $dir . '/.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents(
                $htaccess,
                "Options -Indexes -ExecCGI\n"
                . "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
                . "<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n"
                . "<IfModule mod_php.c>\n\tphp_flag engine off\n</IfModule>\n"
            );
        }

        $webConfig = $dir . '/web.config';
        if (!is_file($webConfig)) {
            @file_put_contents(
                $webConfig,
                "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration><system.webServer>"
                . "<authorization><deny users=\"*\" /></authorization>"
                . "<directoryBrowse enabled=\"false\" />"
                . "</system.webServer></configuration>\n"
            );
        }

        $index = $dir . '/index.html';
        if (!is_file($index)) {
            @file_put_contents($index, "<!DOCTYPE html><title></title>\n");
        }
    }

    /**
     * Percorso assoluto del file a partire da una riga di #__wmacommunication_uploads,
     * validato per stare dentro la cartella base.
     */
    public static function pathFor(object $row): ?string
    {
        $base = self::baseDir();
        $rel  = ($row->subdir !== '' ? trim($row->subdir, '/') . '/' : '') . $row->stored_name;
        $path = $base . '/' . $rel;

        $real     = realpath($path);
        $realBase = realpath($base);

        if ($real === false || $realBase === false) {
            return null;
        }

        $real     = str_replace('\\', '/', $real);
        $realBase = str_replace('\\', '/', $realBase);

        if (strpos($real . '/', $realBase . '/') !== 0 || !is_file($real)) {
            return null;
        }

        return $real;
    }
}
