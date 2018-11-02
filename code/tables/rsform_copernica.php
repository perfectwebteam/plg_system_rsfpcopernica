<?php
/**
 * @package    RsfpCopernica
 *
 * @author     Perfect Web Team <hallo@perfectwebteam.nl>
 * @copyright  Copyright (C) 2018 Perfect Web Team. All rights reserved.
 * @copyright  Copyright (C) 2012 www.comrads.nl
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://perfectwebteam.nl
 */

use Joomla\CMS\Table\Table;

defined('_JEXEC') or die();

/**
 * Copernica form details.
 *
 * @package     RsfpCopernica
 * @since       2.0.0
 */
class TableRsform_Copernica extends Table
{
	/**
	 * Object constructor to set table and key fields.  In most cases this will
	 * be overridden by child classes to explicitly set the table and key fields
	 * for a particular database table.
	 *
	 * @param   \JDatabaseDriver  $db     \JDatabaseDriver object.
	 *
	 * @since   2.0.0
	 */
	public function __construct($db)
	{
		parent::__construct('#__rsform_copernica', 'form_id', $db);
	}
}
