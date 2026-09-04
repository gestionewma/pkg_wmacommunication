<?php
namespace Wma\Component\Wmacommunication\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class FormsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['id', 'a.id', 'title', 'a.title', 'state', 'a.state', 'created', 'a.created', 'checked_out', 'a.checked_out'];
        }

        parent::__construct($config);
    }

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select($db->quoteName(['a.id', 'a.title', 'a.state', 'a.created', 'a.checked_out', 'a.checked_out_time']))
              ->from($db->quoteName('#__wmacommunication_forms', 'a'));

        // Nome dell'utente che ha in modifica il form
        $query->select($db->quoteName('uc.name', 'editor'))
              ->join('LEFT', $db->quoteName('#__users', 'uc') . ' ON ' . $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

        // Filter: state
        $state = $this->getState('filter.state', '');
        if (is_numeric($state)) {
            $query->where($db->quoteName('a.state') . ' = ' . (int) $state);
        }

        // Filter: search
        $search = $this->getState('filter.search', '');
        if (!empty($search)) {
            $query->where($db->quoteName('a.title') . ' LIKE ' . $db->quote('%' . $search . '%'));
        }

        // Order
        $orderCol  = $this->getState('list.ordering', 'a.title');
        $orderDirn = $this->getState('list.direction', 'ASC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
