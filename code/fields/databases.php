<?php
/**
 * @package    RsfpCopernica
 *
 * @author     Perfect Web Team <hallo@perfectwebteam.nl>
 * @copyright  Copyright (C) 2018 Perfect Web Team. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://perfectwebteam.nl
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

defined('_JEXEC') or die;

JFormHelper::loadFieldClass('list');

/**
 * List of available databases.
 *
 * @package  RsfpCopernica
 * @since    2.0.0
 */
class CopernicaFormFieldDatabases extends JFormFieldList
{
	/**
	 * Type of field
	 *
	 * @var    string
	 * @since  2.0.0
	 */
	protected $type = 'Databases';

	/**
	 * The Copernica REST API
	 *
	 * @var    object
	 * @since  2.0.0
	 */
	private $api;

	/**
	 * Build a list of available databases.
	 *
	 * @return  array List of databases.
	 *
	 * @throws  Exception
	 *
	 * @since   2.0.0
	 */
	public function getOptions()
	{
		// Get the REST API
		require_once __DIR__ . '/../rest.php';

		$this->api = new CopernicaRestAPI;
		$this->api->setToken($this->getAccessToken());

		// Get all databases from Copernica
		$oCopernicaDatabases       = $this->api->get('databases');

		// Build the option list
		$aCopernicaDatabaseOptions = array(HTMLHelper::_('select.option', 0, Text::_('PLG_RSFP_COPERNICA_API_NO_DATABASES')));

		if (count($oCopernicaDatabases['data']) > 0)
		{
			$aCopernicaDatabaseOptions = array();

			foreach ($oCopernicaDatabases['data'] as $oCopernicaDatabase)
			{
				$aCopernicaDatabaseOptions[] = HTMLHelper::_('select.option', $oCopernicaDatabase['ID'], $oCopernicaDatabase['name']);
			}
		}

		return array_merge(parent::getOptions(), $aCopernicaDatabaseOptions);
	}

	/**
	 * Get the access token.
	 *
	 * @return  string  The access token.
	 *
	 * @throws  Exception
	 *
	 * @since   2.0.0
	 */
	private function getAccessToken()
	{
		$formId = Factory::getApplication()->input->getInt('formId');

		// Load the table
		Table::addIncludePath(JPATH_PLUGINS . '/system/rsfpcopernica/tables');
		$row = Table::getInstance('Rsform_copernica', 'Table');

		if (!$row)
		{
			return;
		}

		$row->load($formId);

		return $row->get('co_form_password');
	}
}
