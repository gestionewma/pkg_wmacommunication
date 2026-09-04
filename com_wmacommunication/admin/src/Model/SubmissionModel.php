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

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class SubmissionModel extends BaseDatabaseModel
{
    public function getItem(int $pk): ?\stdClass
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__wmacommunication_submissions'))
            ->where($db->quoteName('id') . ' = ' . (int) $pk);

        $item = $db->setQuery($query)->loadObject();

        if (!$item) {
            return null;
        }

        $decoded          = json_decode($item->data ?? '', true);
        $item->data_decoded = is_array($decoded) ? $decoded : [];

        return $item;
    }

    public function markRead(int $pk): void
    {
        $db = $this->getDatabase();
        $db->setQuery(
            $db->getQuery(true)
                ->update($db->quoteName('#__wmacommunication_submissions'))
                ->set($db->quoteName('is_read') . ' = 1')
                ->where($db->quoteName('id') . ' = ' . (int) $pk)
        )->execute();
    }
}
