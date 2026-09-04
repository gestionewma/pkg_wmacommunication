<?php
/**
 * @package     Wma.Module.Wmacommunication
 * @subpackage  mod_wmacommunication
 *
 * @copyright   Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://www.wma.ovh
 */

namespace Wma\Module\Wmacommunication\Site\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Wma\Component\Wmacommunication\Site\View\Form\HtmlView as FormView;

/**
 * Dispatcher di mod_wmacommunication.
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();

        /** @var \Joomla\Registry\Registry $params */
        $params = $data['params'];
        $formId = (int) $params->get('form_id', 0);

        /** @var \Wma\Module\Wmacommunication\Site\Helper\WmacommunicationHelper $helper */
        $helper = $this->getHelperFactory()->getHelper('WmacommunicationHelper');
        $form   = $helper->getForm($formId);

        $data['wmaForm']   = $form;
        $data['wmaView']   = null;
        $data['wmaItemid'] = (int) $data['input']->getInt('Itemid', 0);

        if ($form === null) {
            return $data;
        }

        $doc = $this->getApplication()->getDocument();
        $wa  = $doc->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_wmacommunication');
        $wa->useStyle('com_wmacommunication.site');
        $wa->useScript('com_wmacommunication.site.js');

        $hasHcaptcha = !empty(array_filter(
            $form->fields_decoded,
            static fn($f) => ($f['type'] ?? '') === 'hcaptcha'
        ));

        if ($hasHcaptcha) {
            $wa->registerAndUseScript(
                'com_wmacommunication.hcaptcha',
                'https://js.hcaptcha.com/1/api.js',
                [],
                ['async' => true, 'defer' => true]
            );
        }

        HTMLHelper::_('behavior.keepalive');

        $view = new FormView(['base_path' => JPATH_SITE . '/components/com_wmacommunication']);
        $view->prepareForRender($form);

        $data['wmaView'] = $view;

        return $data;
    }
}
