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
 * @date        02/07/2026
 */

namespace Wma\Component\Wmacommunication\Administrator\View\Forms;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Toolbar\Toolbar;
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
        ToolbarHelper::title(Text::_('COM_WMACOMMUNICATION_FORMS'), 'list');

        $toolbar = Toolbar::getInstance();

        $toolbar->addNew('form.add');
        $toolbar->publish('forms.publish')->listCheck(true);
        $toolbar->unpublish('forms.unpublish')->listCheck(true);

        $toolbar->standardButton('copy', 'COM_WMACOMMUNICATION_DUPLICATE', 'forms.duplicate')->listCheck(true);
        $toolbar->standardButton('download', 'COM_WMACOMMUNICATION_EXPORT', 'forms.export')->listCheck(true);
        $toolbar->standardButton('upload', 'COM_WMACOMMUNICATION_IMPORTSAMPLES', 'forms.importsamples');

        $toolbar->popupButton('import', 'COM_WMACOMMUNICATION_IMPORT')
            ->url(Route::_('index.php?option=com_wmacommunication&task=forms.importform&tmpl=component'))
            ->selector('wmaImportModal')
            ->modalWidth('900px')
            ->modalHeight('500px');

        $toolbar->checkin('forms.checkin')->listCheck(true);
        $toolbar->delete('forms.delete')->message('JGLOBAL_CONFIRM_DELETE')->listCheck(true);

        $toolbar->preferences('com_wmacommunication');
    }
}