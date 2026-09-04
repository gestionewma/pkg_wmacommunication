<?php
/**
 * @package     Wma.Module.Wmacommunication
 * @subpackage  mod_wmacommunication
 *
 * @copyright   Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://www.wma.ovh
 */

namespace Wma\Module\Wmacommunication\Site\Helper;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;

/**
 * Helper per mod_wmacommunication.
 */
class WmacommunicationHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * Carica un form pubblicato del componente com_wmacommunication.
     */
    public function getForm(int $formId): ?\stdClass
    {
        if ($formId <= 0) {
            return null;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__wmacommunication_forms'))
            ->where($db->quoteName('id') . ' = :id')
            ->where($db->quoteName('state') . ' = 1')
            ->bind(':id', $formId, ParameterType::INTEGER);

        $form = $db->setQuery($query)->loadObject();

        if (!$form) {
            return null;
        }

        $decodedFields         = json_decode($form->fields ?? '', true);
        $form->fields_decoded   = \is_array($decodedFields) ? $decodedFields : [];

        $decodedSettings        = json_decode($form->settings ?? '', true);
        $form->settings_decoded = \is_array($decodedSettings) ? $decodedSettings : [];

        return $form;
    }
}
