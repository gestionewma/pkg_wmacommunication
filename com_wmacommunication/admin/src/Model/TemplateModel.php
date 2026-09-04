<?php
namespace Wma\Component\Wmacommunication\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;

class TemplateModel extends AdminModel
{
    public $typeAlias = 'com_wmacommunication.template';

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            'com_wmacommunication.template',
            'template',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    protected function loadFormData()
    {
        $data = $this->getState('template.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    public function getTable($name = 'Template', $prefix = 'Administrator', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }
}
