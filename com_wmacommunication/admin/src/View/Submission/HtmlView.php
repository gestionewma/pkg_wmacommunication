<?php
/**
 * @package     Wma.Component.Wmacommunication
 * @subpackage  com_wmacommunication
 *
 * @copyright   Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Wma\Component\Wmacommunication\Administrator\View\Submission;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

class HtmlView extends BaseHtmlView
{
    protected $item;

    public function display($tpl = null): void
    {
        $id         = (int) Factory::getApplication()->input->getInt('id');
        $this->item = $this->getModel()->getItem($id);

        if (!$this->item) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_WMACOMMUNICATION_SUBMISSION_NOT_FOUND'), 'error');
            Factory::getApplication()->redirect(Route::_('index.php?option=com_wmacommunication&view=submissions', false));
            return;
        }

        if (!$this->item->is_read) {
            $this->getModel()->markRead($id);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_WMACOMMUNICATION_SUBMISSION_DETAIL'), 'inbox');

        $toolbar = Toolbar::getInstance();
        $toolbar->link('JTOOLBAR_CLOSE', Route::_('index.php?option=com_wmacommunication&view=submissions'));
    }
}
