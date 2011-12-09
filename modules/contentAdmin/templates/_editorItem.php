<?php
$contentGroup = $sf_data->getRaw('contentGroup');
$contentBlock = $sf_data->getRaw('contentBlock');
$sf_request = $sf_data->getRaw('sf_request');

// this is the version we're editing now:
$contentBlockVersion = $sf_data->getRaw('contentBlockVersion');

// this is the currently live version:
$currentContentBlockVersion = $contentBlock->getCurrentVersion();

// any errors trying to edit this version:
$errors = (isset($errors) ? $sf_data->getRaw('errors') : array());
$isThisLiveVersion = ($contentBlockVersion->id == $currentContentBlockVersion->id);

$versionHistoryArray = $contentBlock->getEfficientVersionHistoryWithUsers($contentBlockVersion->lang);
$formTarget = ($sf_data->offsetExists('formTarget') ? $sf_data->getRaw('formTarget') : '');
?>

<div class="content_block_editor_item">

<h3><?php echo esc_entities($contentBlock->getDefinitionParam('name')) ?></h3>

<?php if (count($errors) > 0):?>
    <ul class="content_block_editor_item_errors">
        <?php foreach ($errors as $error): ?>
            <li><?php echo esc_entities($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<div style="width:90%;" id="content_block_tabs_<?php echo $contentBlock->identifier; ?>">
	<script type="text/javascript">
		$(document).ready(function () { $('#content_block_tabs_<?php echo $contentBlock->identifier; ?>').tabs(); });
	</script>
	
	<ul>
	    <li><a href='#edit_<?php echo $contentBlock->identifier; ?>'>Edit</a></li>
	    <li><a href='#history_<?php echo $contentBlock->identifier; ?>'>History</a></li>
	</ul>
	
	<div class="section">
		<div id="edit_<?php echo $contentBlock->identifier; ?>">
		    <div class="content_block_editor_control left">
		    	<table class="noBorder">
	            	<?php echo $contentBlockVersion->getContentBlockType()->editRender($sf_request); ?>
	            </table>
		    </div>
		    
		    <div class="content_block_editor_messages right" style="width: 40%;">
		        <?php if ($contentBlock->getDefinitionParam('help')) : ?>
		            <p><span class="ui-icon ui-icon-help left"></span> <?php echo $contentBlock->getDefinitionParam('help'); ?></p>
		        <?php endif; ?>
	
	            <?php if ($contentBlock->useLang()): ?>
	                <p><span class="ui-icon ui-icon-info left"></span> This has different versions for each language</p>
	            <?php else: ?>
	                <p><span class="ui-icon ui-icon-info left"></span> This is shared between all languages</p>
	            <?php endif; ?>
	
	            <?php if (!$contentBlockVersion->isCurrent()): ?>
	                <p class="ui-state-error"><span class="ui-icon ui-icon-alert left"></span> This version is not live</p>
	            <?php endif; ?>
	
	            <?php if (!$contentBlockVersion->isNewest()): ?>
	                <p class="ui-state-error"><span class="ui-icon ui-icon-alert left"></span> There is a newer version of this content</p>
	            <?php endif; ?>
		    </div>
		    
		    <br style="clear:both" />
		</div>
	
		<div id="history_<?php echo $contentBlock->identifier; ?>">
		    <table class="content_block_editor_history">
			    <thead>
			        <tr>
			            <th><?php echo __('Date') ?></th>
			            <th><?php echo __('User') ?></th>
			            <th><?php echo __('Status') ?></th>
			            <td>&nbsp;</td>
			        </tr>
			    </thead>
			    <tbody>
			        <?php foreach ($versionHistoryArray as $version): ?>
			            <tr>
			                <td><?php echo format_datetime($version['created_at']) ?></td>
			                <td><?php echo (is_array($version['CreatedBy']) ? $version['CreatedBy']['username'] : '') ?></td>
			                <td>
			                    <?php if ($version['id'] == $currentContentBlockVersion->id) : ?>
			                        <span class="site_sitetree_published">Live version</span>
			                    <?php endif; ?>
			                    <?php if ($version['id'] == $contentBlockVersion['id']) : ?>
			                        <span class="site_sitetree_editing">Currently editing</span>
			                    <?php endif; ?>
			                </td>
			                <td>
			                    <?php if ($version['id'] != $contentBlockVersion['id']) : ?>
			                    	<form method="post" action="<?php echo $formTarget; ?>">
			                    		<input type="hidden" name="load_version_block_id" value="<?php echo $contentBlock->id; ?>" />
			                    		<input type="hidden" name="load_version_id" value="<?php echo $version['id']; ?>" />
			                        	<input type="submit" class="btn_load frm_submit" name="load_version" value="Load version" />
			                        </form>
			                    <?php endif; ?>
			                </td>
			            </tr>
			        <?php endforeach; ?>
			    </tbody>
		    </table>
		</div>
	  </div>
	</div>
	<br style="clear:both" />
</div>