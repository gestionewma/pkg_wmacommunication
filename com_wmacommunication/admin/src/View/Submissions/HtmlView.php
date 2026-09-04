<?php
/**
 * @package     Wma.Component.Wmacommunication
 * @subpackage  com_wmacommunication
 *
 * @copyright   Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Wma\Component\Wmacommunication\Administrator\View\Submissions;

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
    protected $forms;
    protected $columnLabels;

    public function display($tpl = null): void
    {
        $this->items      = $this->get('Items') ?: [];
        $this->pagination = $this->get('Pagination');
        $this->state      = $this->get('State');
        $this->forms      = $this->get('Forms');

        $model              = $this->getModel();
        $this->columnLabels = $model->getColumnLabels((int) $this->state->get('filter.form_id', 0));

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_WMACOMMUNICATION_SUBMISSIONS'), 'inbox');

        $toolbar = Toolbar::getInstance();

        $toolbar->standardButton('download', 'COM_WMACOMMUNICATION_EXPORT', 'submissions.exportcsv');
        $toolbar->delete('submissions.delete')->message('JGLOBAL_CONFIRM_DELETE')->listCheck(true);

        $toolbar->link('COM_WMACOMMUNICATION_DASHBOARD', Route::_('index.php?option=com_wmacommunication&view=dashboard'));
    }
}
