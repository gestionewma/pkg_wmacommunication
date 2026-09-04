<?php
namespace Wma\Component\Wmacommunication\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;

class FormModel extends AdminModel
{
    public $typeAlias = 'com_wmacommunication.form';

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            'com_wmacommunication.form',
            'form',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    protected function loadFormData()
    {
        $data = $this->getState('form.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    public function getTable($name = 'Form', $prefix = 'Administrator', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }
}
