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

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Wma\Component\Wmacommunication\Administrator\Extension\WmacommunicationComponent;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('\\Wma\\Component\\Wmacommunication'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Wma\\Component\\Wmacommunication'));
        $container->registerServiceProvider(new RouterFactory('\\Wma\\Component\\Wmacommunication'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new WmacommunicationComponent(
                    $container->get(ComponentDispatcherFactoryInterface::class)
                );
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));
                return $component;
            }
        );
    }
};