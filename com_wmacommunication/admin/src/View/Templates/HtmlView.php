<?php
/**
 * @package     Wma.Component.Wmacommunication
 * @subpackage  com_wmacommunication
 *
 * @copyright   Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Wma\Component\Wmacommunication\Administrator\View\Templates;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;

    public function display($tpl = null): void
    {
        $this->items      = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state      = $this->get('State');

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_WMACOMMUNICATION_TEMPLATES'), 'list');

        $toolbar = Toolbar::getInstance();

        $toolbar->addNew('template.add');
        $toolbar->publish('templates.publish')->listCheck(true);
        $toolbar->unpublish('templates.unpublish')->listCheck(true);

        $toolbar->standardButton('download', 'COM_WMACOMMUNICATION_EXPORT', 'templates.export')->listCheck(true);
        $toolbar->standardButton('upload', 'COM_WMACOMMUNICATION_TEMPLATE_IMPORTSAMPLES', 'templates.installsamples');

        $toolbar->popupButton('import', 'COM_WMACOMMUNICATION_IMPORT')
            ->url(Route::_('index.php?option=com_wmacommunication&task=templates.importform&tmpl=component'))
            ->selector('wmaTemplateImportModal')
            ->modalWidth('900px')
            ->modalHeight('500px');

        $toolbar->delete('templates.delete')->message('JGLOBAL_CONFIRM_DELETE')->listCheck(true);

        $toolbar->link('COM_WMACOMMUNICATION_DASHBOARD', Route::_('index.php?option=com_wmacommunication&view=dashboard'));
    }
}
