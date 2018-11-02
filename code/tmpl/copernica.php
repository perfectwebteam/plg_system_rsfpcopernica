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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

HTMLHelper::_('script', 'plg_rsfpcopernica/rsfpcopernica.js', array('version' => 'auto', 'relative' => true));
HTMLHelper::_('formbehavior.chosen');

?>
<div id="copernicadiv">
	<script type="text/javascript">
		rsfpCopernica.addFields('<?php echo json_encode($fieldsArray); ?>');
		rsfpCopernica.addMergeValues('<?php echo json_encode($row->co_merge_vars); ?>');
		rsfpCopernica.addMergeUpdateValues('<?php echo json_encode($row->co_merge_vars_update); ?>');
		rsfpCopernica.addMergeKeys('<?php echo json_encode($row->co_merge_vars_key); ?>');
		rsfpCopernica.addMergeIgnoreKeys('<?php echo json_encode($row->co_merge_vars_ignore); ?>');

		jQuery(document).ready(function() {
			// Set initial view for default fields
			if (jQuery('#copernicaParams_co_form_list_id').val() != 0 && jQuery('#copernicaParams_co_form_list_id').val() != '' && jQuery('#copernicaParams_co_form_list_id').val() != null) {
				rsfpCopernica.rsfp_changeCoDatabases(jQuery('#copernicaParams_co_form_list_id').val());
			}

			rsfpCopernica.rsfp_changeCoMapping();
		});
	</script>

	<?php echo HTMLHelper::_('image', 'media/plg_rsfpcopernica/images/copernica.jpg', 'Copernica'); ?>
	<?php echo $form->renderFieldset('formConfig'); ?>
	<table class="table table-striped">
		<thead>
		<tr>
			<th><?php echo Text::_('PLG_RSFP_COPERNICA_MERGE_VARS_TABLE_HEADING_RSFORM_FIELD'); ?></th>
			<th><?php echo Text::_('PLG_RSFP_COPERNICA_MERGE_VARS_TABLE_HEADING_COPERNICA_FIELD'); ?></th>
			<th><?php echo Text::_('PLG_RSFP_COPERNICA_MERGE_VARS_TABLE_HEADING_KEY'); ?></th>
			<th><?php echo Text::_('PLG_RSFP_COPERNICA_MERGE_VARS_TABLE_HEADING_IGNORE_IF_EXISTS'); ?></th>
			<th><?php echo Text::_('PLG_RSFP_COPERNICA_MERGE_VARS_TABLE_HEADING_ADD_TO_EXISTING'); ?></th>
		</tr>
		</thead>
		<tbody id="co_merge_vars" class="co_mapping">

		</tbody>
	</table>
	<div id="co_state"></div>
</div>
