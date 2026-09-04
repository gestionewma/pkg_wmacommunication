<?php
/**
 * @package     Wma.Component.Wmacommunication
 * @subpackage  com_wmacommunication
 *
 * @copyright   Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Wma\Component\Wmacommunication\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Filter\InputFilter;

class SubmissionsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['id', 'a.id', 'form_id', 'a.form_id', 'created', 'a.created', 'is_read', 'a.is_read'];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.created', $direction = 'DESC')
    {
        $app = Factory::getApplication();

        $formId = $app->input->getInt('filter_form_id', $app->getUserState('com_wmacommunication.submissions.filter.form_id', 0));
        $this->setState('filter.form_id', $formId);
        $app->setUserState('com_wmacommunication.submissions.filter.form_id', $formId);

        $isRead = $app->input->getString('filter_is_read', $app->getUserState('com_wmacommunication.submissions.filter.is_read', ''));
        $this->setState('filter.is_read', $isRead);
        $app->setUserState('com_wmacommunication.submissions.filter.is_read', $isRead);

        $search = $app->input->getString('filter_search', $app->getUserState('com_wmacommunication.submissions.filter.search', ''));
        $search = InputFilter::getInstance()->clean($search, 'string');
        $this->setState('filter.search', $search);
        $app->setUserState('com_wmacommunication.submissions.filter.search', $search);

        parent::populateState($ordering, $direction);
    }

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select($db->quoteName([
                  'a.id', 'a.form_id', 'a.form_title', 'a.summary',
                  'a.col1_label', 'a.col1_value', 'a.col2_label', 'a.col2_value',
                  'a.ip', 'a.is_read', 'a.created',
              ]))
              ->from($db->quoteName('#__wmacommunication_submissions', 'a'));

        $formId = (int) $this->getState('filter.form_id', 0);
        if ($formId > 0) {
            $query->where($db->quoteName('a.form_id') . ' = ' . $formId);
        }

        $isRead = $this->getState('filter.is_read', '');
        if ($isRead !== '' && is_numeric($isRead)) {
            $query->where($db->quoteName('a.is_read') . ' = ' . (int) $isRead);
        }

        $search = $this->getState('filter.search', '');
        if (!empty($search)) {
            $query->where(
                '(' . $db->quoteName('a.summary') . ' LIKE ' . $db->quote('%' . $search . '%')
                . ' OR ' . $db->quoteName('a.data') . ' LIKE ' . $db->quote('%' . $search . '%')
                . ')'
            );
        }

        $orderCol  = $this->getState('list.ordering', 'a.created');
        $orderDirn = $this->getState('list.direction', 'DESC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    /**
     * Elenco form (id/title) presenti tra gli invii, per la select del filtro.
     */
    public function getForms(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('DISTINCT ' . $db->quoteName('form_id') . ', ' . $db->quoteName('form_title'))
            ->from($db->quoteName('#__wmacommunication_submissions'))
            ->order($db->quoteName('form_title') . ' ASC');

        return $db->setQuery($query)->loadObjectList();
    }

    /**
     * Etichette delle colonne 1/2 per il form attualmente filtrato, lette dalla
     * definizione campi corrente (non dai dati storici delle submission).
     * Vuoto se non c'è un form filtrato o nessun campo è assegnato a quella colonna.
     */
    public function getColumnLabels(int $formId): array
    {
        $labels = ['1' => '', '2' => ''];

        if ($formId <= 0) {
            return $labels;
        }

        $db     = $this->getDatabase();
        $fields = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName('fields'))
                ->from($db->quoteName('#__wmacommunication_forms'))
                ->where($db->quoteName('id') . ' = ' . $formId)
        )->loadResult();

        $decoded = json_decode($fields ?? '', true);
        if (!is_array($decoded)) {
            return $labels;
        }

        foreach ($decoded as $field) {
            $col = (string) ($field['list_column'] ?? '');
            if (isset($labels[$col]) && $labels[$col] === '' && in_array($field['type'] ?? '', ['text', 'email'], true)) {
                $labels[$col] = (string) ($field['label'] ?? '');
            }
        }

        return $labels;
    }

    public function getUnreadCount(): int
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__wmacommunication_submissions'))
            ->where($db->quoteName('is_read') . ' = 0');

        return (int) $db->setQuery($query)->loadResult();
    }
}
