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

namespace Wma\Component\Wmacommunication\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class SubmitController extends BaseController
{
    public function submit(): void
    {
        $this->checkToken();

        $app    = Factory::getApplication();
        $model  = $this->getModel('Form', 'Site');
        $data   = $app->input->post->getArray([]);
        $formId = (int) $app->input->getInt('form_id');
        $itemId = (int) $app->input->getInt('Itemid');

        $result = $model->processSubmission($data, $formId);

        // Pulisce eventuali dati di sessione precedenti
        $session = $app->getSession();
        $session->set('com_wmacommunication.form_data_' . $formId, null);

        if (is_array($result) && $result['success'] === true) {
            $fieldValues = $result['fieldValues'];
            $settings    = $result['settings'] ?? $model->getSettings($formId);
            $successMsg  = trim($settings['success_msg'] ?? '');

            if ($successMsg) {
                $successMsg = $model->resolvePlaceholders($successMsg, $fieldValues);
            }

            $app->enqueueMessage($successMsg ?: Text::_('COM_WMACOMMUNICATION_SUBMIT_SUCCESS'), 'success');
        } else {
            // Salva i dati inseriti in sessione per ripopolare il form
            $session->set('com_wmacommunication.form_data_' . $formId, $data);
            $app->enqueueMessage(is_string($result) ? $result : Text::_('COM_WMACOMMUNICATION_SUBMIT_ERROR'), 'error');
        }

        $url = 'index.php?Itemid=' . $itemId;
        $this->setRedirect(Route::_($url, false));
    }
}