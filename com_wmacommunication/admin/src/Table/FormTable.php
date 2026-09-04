<?php
/**
 * @file        admin/src/Table/FormTable.php
 * @package     Wma.Component.Wmacommunication
 * @subpackage  com_wmacommunication
 *
 * @author      WMA Web Maker Agency
 * @copyright   (C) 2026 WMA Web Maker Agency
 * @license     GNU General Public License version 2 or later
 */

namespace Wma\Component\Wmacommunication\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class FormTable extends Table
{
    public ?int    $checked_out      = null;
    public ?string $checked_out_time = null;

    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__wmacommunication_forms', 'id', $db);
        $this->setColumnAlias('published', 'state');
    }
}
