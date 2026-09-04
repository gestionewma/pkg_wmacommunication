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

/**
 * Contesto della pagina corrente, scritto da plg_content_wmacommunication
 * (onContentPrepare) e letto da HtmlView::currentArticleTitle().
 *
 * Vive solo per la durata della richiesta (nessuna persistenza): PHP non
 * condivide stato tra richieste in esecuzione standard PHP-FPM/Apache.
 */
abstract class PageContextHelper
{
    private static string $articleTitle = '';

    public static function setArticleTitle(string $title): void
    {
        $title = trim($title);

        if ($title !== '') {
            self::$articleTitle = $title;
        }
    }

    public static function getArticleTitle(): string
    {
        return self::$articleTitle;
    }
}
