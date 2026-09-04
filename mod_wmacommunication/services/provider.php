<?php
/**
 * @package     Wma.Module.Wmacommunication
 * @subpackage  mod_wmacommunication
 *
 * @copyright   Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://www.wma.ovh
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\Wma\\Module\\Wmacommunication'));
        $container->registerServiceProvider(new HelperFactory('\\Wma\\Module\\Wmacommunication\\Site\\Helper'));
        $container->registerServiceProvider(new Module());
    }
};
