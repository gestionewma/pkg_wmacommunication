<?php
/**
 * @package     pkg_wmacommunication
 *
 * @copyright   Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://www.wma.ovh
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class Pkg_WmacommunicationInstallerScript
{
    public function preflight(string $type, InstallerAdapter $parent): bool
    {
        if (version_compare(JVERSION, '6.0', '<')) {
            Factory::getApplication()->enqueueMessage(
                'WMA Communication richiede Joomla 6.0 o superiore.',
                'error'
            );

            return false;
        }

        return true;
    }

    public function postflight(string $type, InstallerAdapter $parent): bool
    {
        $app = Factory::getApplication();

        $verb = $type === 'update' ? 'aggiornato' : 'installato';

        $app->enqueueMessage(
            '<strong>WMA Communication ' . $verb . '.</strong> '
            . 'Componente e modulo sono pronti. Il modulo <em>mod_wmacommunication</em> va assegnato '
            . 'a una posizione del template e collegato a un form pubblicato.',
            'success'
        );

        return true;
    }
}
