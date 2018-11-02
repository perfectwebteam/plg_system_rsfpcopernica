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

use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Table\Table;

defined('_JEXEC') or die();

/**
 * RSForm! Pro system plugin.
 *
 * @since 1.0.0
 */
class plgSystemRSFPCopernica extends CMSPlugin
{
	/**
	 * Database driver
	 *
	 * @var    JDatabaseDriver
	 * @since  2.0.0
	 */
	protected $db;

	/**
	 * An application instance
	 *
	 * @var    JApplicationSite
	 * @since  2.0.0
	 */
	protected $app;

	/**
	 * Autoload the language
	 *
	 * @var    boolean
	 * @since  2.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * The Copernica REST API
	 *
	 * @var    object
	 * @since  2.0.0
	 */
	private $api;

	/**
	 * Constructor
	 *
	 * @param   object  $subject  The object to observe
	 * @param   array   $config   An optional associative array of configuration settings.
	 *                            Recognized key values include 'name', 'group', 'params', 'language'
	 *                            (this list is not meant to be comprehensive).
	 *
	 * @since   2.0.0
	 */
	public function __construct(object $subject, array $config = array())
	{
		parent::__construct($subject, $config);

		require_once __DIR__ . '/rest.php';

		$this->api = new CopernicaRestAPI;
	}

	/**
	 * Check if RSFormPro is loaded.
	 *
	 * @return  boolean  The field option objects.
	 *
	 * @since   2.0.0
	 */
	public function canRun()
	{
		if (class_exists('RSFormProHelper'))
		{
			return true;
		}

		$helper = JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/rsform.php';

		if (file_exists($helper))
		{
			require_once $helper;
			RSFormProHelper::readConfig(true);

			return true;
		}

		return false;
	}

	/**
	 * Add the option under Extras when editing the form properties.
	 *
	 * @return  void.
	 *
	 * @since   2.0.0
	 */
	public function rsfp_bk_onAfterShowFormEditTabsTab()
	{
		echo '<li>' . HTMLHelper::link(
				'javascript: void(0);',
				'<span class="rsficon rsficon-envelope-o"></span><span class="inner-text">' . Text::_('PLG_RSFP_COPERNICA_LABEL') . '</span>'
			) . '</li>';
	}

	/**
	 * Add settings.
	 *
	 * @return  void.
	 *
	 * @since   2.0.0
	 *
	 * @throws  Exception
	 */
	public function rsfp_bk_onAfterShowFormEditTabs()
	{
		// Get the form ID to get the Copernica details for
		$formId = $this->app->input->getInt('formId');

		// Load the table
		Table::addIncludePath(JPATH_PLUGINS . '/system/rsfpcopernica/tables');
		$row = Table::getInstance('Rsform_copernica', 'Table');

		if (!$row)
		{
			return;
		}

		// Load the Copernica form details
		$row->load($formId);

		$row = $this->cleanRowDetails($row);

		if (empty($row->co_form_accountname))
		{
			?>
	<div id="rsfpverticalresponsediv">
				<table class="admintable">
					<tr>
						<td valign="top" align="left" width="30%">
							<table class="table table-bordered">
								<div class="alert alert-warning"><?php echo Text::_('PLG_RSFP_COPERNICA_NOTOKEN') ?></div>
							</table>
						</td>
					</tr>
				</table>
			</div>
			<?php
			return;
		}

		// Get all RS Form! Fields
		$fieldsArray = $this->getFields($formId);
		$fields      = array();

		foreach ($fieldsArray as $field)
		{
			$fields[] = HTMLHelper::_('select.option', $field, $field);
		}

		$form = new Form('rsfpcopernica');
		$form->loadFile(__DIR__ . '/configuration.xml');
		$data = array('copernicaParams' => $row);

		$form->bind($data);

		require __DIR__ . '/tmpl/copernica.php';
	}

	/**
	 * Get the form fields for mapping to Copernica.
	 *
	 * @param   integer  $formId  The ID of the active form
	 *
	 * @return  array  List of form fields.
	 *
	 * @since   2.0.0
	 */
	private function getFields(int $formId)
	{
		$db    = $this->db;
		$query = $db->getQuery(true)
			->select($db->quoteName('properties.PropertyValue', 'Componentname'))
			->select($db->quoteName('types.ComponentTypeName'))
			->from($db->quoteName('#__rsform_components', 'components'))
			->leftJoin($db->quoteName('#__rsform_properties', 'properties')
				. ' ON ' . $db->quoteName('components.ComponentId') . ' = ' . $db->quoteName('properties.ComponentId')
				. ' AND ' . $db->quoteName('properties.PropertyName') . ' = ' . $db->quote('NAME')
			)
			->leftJoin($db->quoteName('#__rsform_component_types', 'types')
				. ' ON ' . $db->quoteName('types.ComponentTypeId') . ' = ' . $db->quoteName('components.ComponentTypeId')
			)
			->where($db->quoteName('components.FormId') . ' = ' . (int) $formId)
			->order($db->quoteName('components.Order'));

		$db->setQuery($query);
		$fields = $db->loadColumn();

		$sIgnoreText = Text::_('PLG_RSFP_COPERNICA_API_MAPPING_IGNORE');
		array_unshift($fields, $sIgnoreText, 'Form ID', 'Submission ID');

		return $fields;
	}


	/**
	 * Update the conditions after form save.
	 *
	 * @param   object $form The form object that is being stored.
	 *
	 * @return  mixed  True on success | False on failure | void if not used
	 *
	 * @throws  Exception
	 * @throws  RuntimeException
	 *
	 * @since   2.0.0
	 */
	public function rsfp_onFormSave($form)
	{
		$params = $this->app->input->post->get('copernicaParams', array(), 'array');

		// Load the table
		Table::addIncludePath(JPATH_PLUGINS . '/system/rsfpcopernica/tables');
		$row = Table::getInstance('Rsform_copernica', 'Table');

		if (!$row)
		{
			return;
		}

		// Load the existing data
		$row->set('form_id', (int) $form->FormId);
		$row->load();

		// Bind the new data
		try
		{
			$row->bind($params);
		}
		catch (Exception $exception)
		{
			$this->app->enqueueMessage($exception->getMessage(), 'error');

			return false;
		}

		// Prepare the data to store
		$row->set('co_merge_vars', serialize($this->app->input->post->get('co_merge_vars', array(), 'array')));
		$row->set('co_merge_vars_update', serialize($this->app->input->post->get('co_merge_vars_update', array(), 'array')));
		$row->set('co_merge_vars_ignore', serialize($this->app->input->post->get('co_merge_vars_ignore', array(), 'array')));
		$row->set('co_merge_vars_key', serialize($this->app->input->post->get('co_merge_vars_key', array(), 'array')));

		if (!$row->store())
		{
			$this->app->enqueueMessage($row->getError(), 'error');

			return false;
		}

		return true;
	}

	/**
	 * Read the fields when the database changes.
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   2.0.0
	 */
	public function rsfp_bk_onSwitchTasks()
	{
		$formId = $this->app->input->getInt('formId');

		// Load the table
		Table::addIncludePath(JPATH_PLUGINS . '/system/rsfpcopernica/tables');
		$row = Table::getInstance('Rsform_copernica', 'Table');

		if (!$row)
		{
			return;
		}

		$row->load($formId);

		$row = $this->cleanRowDetails($row);

		$pluginTask = $this->app->input->getCmd('co_plugin_task');

		switch ($pluginTask)
		{
			case 'get_merge_vars':

				if ($row->get('co_form_password') === '')
				{
					echo '(Copernica) ' . Text::_('RSFP_COPERNICA_API_MISSING_CREDENTIALS');

					exit();
				}

				// Get all database fields from Copernica for a specific database ID
				$databaseId = $this->app->input->getInt('list_id');

				// Get the HTTP transport
				$this->api->setToken($row->get('co_form_password'));

				// Get All databases from Copernica
				$oCopernicaDatabaseFields = $this->api->get('database/' . $databaseId . '/fields');

				echo (new JsonResponse($oCopernicaDatabaseFields['data']));
				break;
		}

		$this->app->close();
	}

	/**
	 * Preprocess before sending the admin email.
	 *
	 * @param   array  $args  The submission details
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   2.0.0
	 */
	public function rsfp_beforeAdminEmail($args)
	{
		$form = $args['form'];
		$db   = $this->db;

		// Get the status
		$query = $db->getQuery(true)
			->select($db->quoteName('FieldValue'))
			->from($db->quoteName('#__rsform_submission_values'))
			->where($db->quoteName('FieldName') . ' = ' . $db->quote('_STATUS'))
			->where($db->quoteName('SubmissionId') . ' = ' . (int) $args['submissionId']);
		$db->setQuery($query);

		$status = $db->loadResult();

		$query->clear('where')
			->where($db->quoteName('FieldName') . ' = ' . $db->quote('betaalwijze_methode'))
			->where($db->quoteName('SubmissionId') .  ' = ' . (int) $args['submissionId']);
		$db->setQuery($query);
		$betaalwijze = $db->loadResult();

		// Send to Copernica if paid via iDEAL
		if ((int) $status === 1 && strtolower($betaalwijze) !== 'incasso')
		{
			$this->sendCopernica($args);
		}

		// Send directly to Copernica if offlinepayment
		if (strtolower($betaalwijze) === 'incasso')
		{
			$query = $db->getQuery(true)
				->update($db->quoteName('#__rsform_submission_values'))
				->set($db->quoteName('FieldValue') . '=1')
				->where($db->quoteName('SubmissionId') . ' = ' . (int) $args['submissionId'])
				->where($db->quoteName('FormId') . ' = ' . (int) $form->FormId)
				->where($db->quoteName('FieldName') . ' =  ' . $db->quote('_STATUS'));
			$db->setQuery($query)
				->execute();

			$this->sendCopernica($args);
		}
	}

	/**
	 * Send the submission to Copernica.
	 *
	 * @param   array  $args  The submission details
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   2.0.0
	 */
	private function sendCopernica($args)
	{
		$db = $this->db;

		$formId       = (int) $args['form']->FormId;
		$submissionId = (int) $args['submissionId'];

		$form = array();
		$query = $db->getQuery(true)
			->select(
				$db->quoteName(
					array(
						'submissionValues.FieldName',
						'submissionValues.FieldValue'
					)
				)
			)
			->from($db->quoteName('#__rsform_submissions', 'submissions'))
			->leftJoin(
				$db->quoteName('#__rsform_submission_values', 'submissionValues')
				. ' ON ' . $db->quoteName('submissionValues.SubmissionId') . ' = ' . $db->quoteName('submissions.SubmissionId')
			)
			->where($db->quoteName('submissions.SubmissionId') . ' = ' . (int) $submissionId)
			->where($db->quoteName('submissionValues.SubmissionId') . ' = ' . (int) $submissionId);
		$db->setQuery($query);

		$fields = $db->loadObjectList();

		foreach ($fields as $field)
		{
			$form[$field->FieldName] = $field->FieldValue;
		}

		// Load the table
		Table::addIncludePath(JPATH_PLUGINS . '/system/rsfpcopernica/tables');
		$row = Table::getInstance('Rsform_copernica', 'Table');

		// Load the row
		if ($row->load($formId))
		{
			// Get the database ID
			$databaseId = $row->get('co_form_list_id');

			if (!$row->get('co_form_active') || !$databaseId)
			{
				return;
			}

			if ($row->get('co_form_password') === '')
			{
				throw new RuntimeException(Text::_('RSFP_COPERNICA_API_MISSING_CREDENTIALS'));
			}

			list($replace, $with) = RSFormProHelper::getReplacements($submissionId);

			// Clean the details
			$row = $this->cleanRowDetails($row);

			// Default ignore string
			$sIgnoreText = Text::_('RSFP_COPERNICA_API_MAPPING_IGNORE');

			// Get the data from the form that needs to be merged with an existing entry or used as a new entry
			$mergeData = array();

			foreach ($row->get('co_merge_vars') as $tag => $field)
			{
				if ($field == $sIgnoreText)
				{
					continue;
				}

				if ($field === 'Form ID')
				{
					$form[$field] = $formId;
				}

				if ($field === 'Submission ID')
				{
					$form[$field] = $submissionId;
				}

				if (!isset($form[$field]))
				{
					$form[$field] = '';
				}

				if (is_array($form[$field]))
				{
					array_walk($form[$field], array('plgSystemRSFPCopernica', 'escapeCommas'));
					$form[$field] = implode(',', $form[$field]);
				}

				$mergeData[$tag] = $form[$field];
			}

			// Get the HTTP transport
			$this->api->setToken($row->get('co_form_password'));

			// Get all database fields
			$oCopernicaDatabaseFields = $this->api->get('database/' . $databaseId . '/fields');

			// Set some default values
			$aMatchedFields           = array();
			$aUpdateArray             = array();
			$aDBFieldArray            = array();
			$aUpdateprofiles          = array();
			$aUpdateprofiles['count'] = 0;

			// Get a list of key fields that should be used to identify existing profiles
			foreach ($oCopernicaDatabaseFields['data'] as $oCopernicaDatabaseField)
			{
				// Check if this field is also in the form submit.
				if (isset($mergeData[$oCopernicaDatabaseField['ID']]) && $mergeData[$oCopernicaDatabaseField['ID']] !== '')
				{
					$aUpdateArray[$oCopernicaDatabaseField['name']] = $mergeData[$oCopernicaDatabaseField['ID']];
					$aDBFieldArray[$oCopernicaDatabaseField['ID']]  = $oCopernicaDatabaseField['name'];
				}

				foreach ($row->get('co_merge_vars_key') as $k => $v)
				{
					if ($k === $oCopernicaDatabaseField['name'])
					{
						$aMatchedFields[$oCopernicaDatabaseField['ID']] = $oCopernicaDatabaseField['name'];
					}
				}
			}

			try
			{
				// Look for any existing profiles
				if (count($aMatchedFields) > 0)
				{
					// Let's build the requirements array for Copernica
					$aRequirements = array();

					foreach ($aMatchedFields as $sMatchKey => $sMatchValue)
					{
						if ($mergeData[$sMatchKey] !== '')
						{
							$aRequirements['field='][] = $sMatchValue . '==' . $mergeData[$sMatchKey];
						}
					}

					// Get the profiles from Copernica
					$aUpdateprofiles = $this->api->get('database/' . $databaseId . '/profiles', $aRequirements);
				}

				// Check if we have any profiles to update
				if ($aUpdateprofiles['count'] > 0)
				{
					// We found profiles so let's update
					foreach ($aUpdateprofiles['data'] as $profile)
					{
						// Check if we have add to existing values set
						$mergeVariablesUpdate = $row->get('co_merge_vars_update');

						if (count($mergeVariablesUpdate) > 0)
						{
							foreach ($mergeVariablesUpdate as $k => $v)
							{
								if (isset($aUpdateArray[$aDBFieldArray[$k]]) && $aUpdateArray[$aDBFieldArray[$k]] !== '' && (int) $v === 1)
								{
									foreach ($profile['fields'] as $pk => $pv)
									{
										if ($pv->key == $aDBFieldArray[$k])
										{
											if ($pv->value != '')
											{
												$pv->value = $pv->value . '+';
											}

											$aUpdateArray[$aDBFieldArray[$k]] = $pv->value . $aUpdateArray[$aDBFieldArray[$k]];
										}
									}
								}
							}
						}

						// Check if we have ignore values
						$variablesIgnore = $row->get('co_merge_vars_ignore');

						if (count($variablesIgnore) > 0)
						{
							foreach ($variablesIgnore as $k => $v)
							{
								if (isset($aUpdateArray[$aDBFieldArray[$k]]) && $aUpdateArray[$aDBFieldArray[$k]] !== '' && (int) $v == 1)
								{
									foreach ($profile['fields'] as $pk => $pv)
									{
										if ($pv->key === $aDBFieldArray[$k])
										{
											if ($pv->value !== '')
											{
												$aUpdateArray[$aDBFieldArray[$k]] = $pv->value;
											}
										}
									}
								}
							}
						}

						$this->api->put('profile/' . $profile['ID'], $aUpdateArray);
					}
				}
				else
				{
					// We didn't find anything so let's create a new profile
					$this->api->post('database/' . $databaseId . '/profiles', $aUpdateArray);
				}
			}
			catch (Exception $e)
			{
				print_r($e);
			}
		}
	}

	/**
	 * Clean the details.
	 *
	 * @param   Table  $row  The table row to cleanup
	 *
	 * @return  Table  The Copernica row.
	 *
	 * @since   2.0.0
	 */
	private function cleanRowDetails($row)
	{
		// Clean up the details
		$copernicaVariables = array(
			'co_merge_vars',
			'co_merge_vars_update',
			'co_merge_vars_ignore',
			'co_merge_vars_key'
		);

		foreach ($copernicaVariables as $copernicaVariable)
		{
			$row->set($copernicaVariable, unserialize($row->$copernicaVariable));

			if ($row->get($copernicaVariable) === false)
			{
				$row->set($copernicaVariable, array());
			}
		}

		return $row;
	}

	/**
	 * Escape comma's in a string.
	 *
	 * @param   string  $item  The item to replace
	 *
	 * @return  void
	 *
	 * @since   2.0.0
	 */
	public function escapeCommas(&$item)
	{
		$item = str_replace(',', '\,', $item);
	}
}
