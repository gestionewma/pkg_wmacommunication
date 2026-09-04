<?php
/**
 * @package     Wma.Plugin.Content.Wmacommunication
 *
 * @copyright   Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://www.wma.ovh
 */

namespace Wma\Plugin\Content\Wmacommunication\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Wma\Component\Wmacommunication\Site\Helper\PageContextHelper;

/**
 * Cattura il titolo dell'articolo che Joomla sta montando, per i campi
 * automatici "Titolo articolo" di com_wmacommunication (vedi
 * Site\Helper\PageContextHelper e Site\View\Form\HtmlView::currentArticleTitle()).
 *
 * Copre: modulo del form in una posizione del template mentre si visualizza un
 * singolo articolo, e form inserito con {loadposition} dentro il testo
 * dell'articolo (in quest'ultimo caso l'ordine dei plugin nel gruppo Contenuto
 * conta: questo plugin deve girare PRIMA di "Content - Carica modulo").
 */
final class Wmacommunication extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepare' => 'onContentPrepare',
        ];
    }

    public function onContentPrepare(ContentPrepareEvent $event): void
    {
        // Solo la vista del singolo articolo: in un elenco/blog il "titolo
        // dell'articolo corrente" non avrebbe un significato univoco.
        if ($event->getContext() !== 'com_content.article') {
            return;
        }

        if (!class_exists(PageContextHelper::class)) {
            return;
        }

        $article = $event->getItem();

        if (isset($article->title)) {
            PageContextHelper::setArticleTitle((string) $article->title);
        }
    }
}
