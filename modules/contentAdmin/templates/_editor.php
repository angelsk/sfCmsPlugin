<?php
use_helper('jQuery');

$sf_request = $sf_data->getRaw('sf_request');
$contentGroup = $sf_data->getRaw('contentGroup');
$contentBlocks = $sf_data->getRaw('contentBlocks');
$contentBlockVersions = $sf_data->getRaw('contentBlockVersions');

$formId = 'ContentBlockEditor' . $contentGroup->id;
$lang = $contentGroup->getCurrentLang();
$formTarget = ($sf_data->offsetExists('formTarget') ? $sf_data->getRaw('formTarget') : '');
?>

<br />

<?php if ($sf_user->hasFlash('Content_error')): ?>
  <div class="ui-widget">
	<div class="ui-state-error ui-corner-all" style="margin: 10px; padding: 7px 0px 0px 7px;"> 
		<p><span class="ui-icon ui-icon-alert left"></span> 
		<?php echo $sf_user->getFlash('Content_error'); ?></p>
	</div>
  </div>
<?php endif; ?>

<?php if ($sf_user->hasFlash('Content_notice')): ?>
  <div class="ui-widget">
	 <div class="ui-state-highlight ui-corner-all" style="margin: 10px; padding: 7px 0px 0px 7px;"> 
		<p><span class="ui-icon ui-icon-info left"></span>
		<?php echo $sf_user->getFlash('Content_notice'); ?></p>
	 </div>
  </div>
<?php endif; ?>

<div class="Content_block_editor">
	<form id="<?php echo $formId ?>" method="post" action="<?php echo $formTarget; ?>" enctype="multipart/form-data">
		<fieldset class="sitetreeInfo">
	    	<span class="left" style="margin: 5px 5px 0 0;">Load versions:</span>
	        <input type="submit" name="live_versions" value="Currently live" class="btn_load float_r frm_submit" />
	        <input type="submit" name="new_versions" value="Newest" class="btn_load float_r frm_submit" />
		</fieldset>
		
	    <fieldset>
	        <input type="hidden" name="Content_group_id" value="<?php echo $contentGroup->id?>" />
	        <input type="hidden" name="sf_culture" value="<?php echo $sf_user->getCulture(); ?>" />
	        <input type="hidden" name="lang" value="<?php echo esc_entities($lang) ?>" />
	        
	        <div class='Content_block_editor_items'>
	            <?php foreach ($contentBlocks as $contentBlock): ?>
	                <?php $contentBlockVersion = $contentBlockVersions[$contentBlock->identifier]; ?>
	
	                <?php include_partial('contentAdmin/editorItem', array('ContentGroup' => $contentGroup, 'ContentBlock' => $contentBlock, 'ContentBlockVersion' => $contentBlockVersion, 'formTarget' => $formTarget)); ?>
	            <?php endforeach; ?>
	        </div>
	    </fieldset>
	
	    <div style="display: none;" class="Content_block_editor_loading"><img src="/sitePlugin/images/ajax-bar.gif" alt="Loading..." /></div>
	
	    <fieldset class="sitetreeInfo">
	        <input type="submit" name="preview" id="<?php echo $formId ?>Preview" value="Preview" class="btn_save float_r frm_submit" />
	        <input type="submit" name="save" value="Save version" class="btn_save float_r frm_submit" />
	        <input type="submit" name="save_and_publish" value="Save and publish" class="btn_save float_r frm_submit"/>
	    	
	    	<?php if (isset($cancelUrl)) echo button_to('Cancel', $cancelUrl, array('class'=>'btn_cancel float_r frm_submit')); ?>
	    	<br class="clear" />
		</fieldset>
	    <br/>
	
	    <iframe style="width: 95%; height: 500px;" name="<?php echo $formId ?>Iframe" id="<?php echo $formId ?>Iframe"></iframe>
	</form>
</div>

<script type="text/javascript">
	$(document).ready(function () {
		// hide the iframe
		$('#<?php echo $formId ?>Iframe').hide(); 
		
		$('#<?php echo $formId ?>Preview').click(function(event) {
			// stop form submit
			event.stopPropagation();

			// show the iframe
		    $('#<?php echo $formId ?>Iframe').show();
		    var form = $('#<?php echo $formId ?>');
		    
		    // post our form to the iframe so it can render the Content blocks
		    form.attr('action', '<?php echo $previewUrl ?>');
		    form.attr('target', '<?php echo $formId ?>Iframe');
		    form.submit();

		    // reset so can submit save / publish
		    form.attr('action', '<?php echo $formTarget; ?>');
		    form.attr('target', '');
		    return false;
		});
	});
</script>

<?php
$javascript = "";
foreach ($contentBlockVersions as $contentBlockVersion) 
{
	$versionJs =  $contentBlockVersion->getContentBlockType()->editRenderJavascript($sf_request);
    if (!empty($versionJs)) 
	{
    	$javascript .= "\n\n//Inititialisation javascript for Content block version '" . esc_entities($contentBlockVersion->id) . "'\n" . $versionJs;
    }
}
?>

<?php if (!empty($javascript)) : ?>
	<script type="text/javascript">
	//<![CDATA[
		$(document).ready(function() 
		{
			<?php $javascript; ?>
		});
	});
	</script>
<?php endif; ?>
