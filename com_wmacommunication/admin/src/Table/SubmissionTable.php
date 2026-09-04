<?php
/**
 * @file        admin/src/Table/SubmissionTable.php
 * @package     Wma.Component.Wmacommunication
 * @subpackage  com_wmacommunication
 *
 * @copyright   Copyright (C) 2026 Gestionewma. Tutti i diritti riservati.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Wma\Component\Wmacommunication\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class SubmissionTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__wmacommunication_submissions', 'id', $db);
    }
}
