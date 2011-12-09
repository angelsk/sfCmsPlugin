<?php
$sitetree = $sf_data->getRaw('sitetree');
$page = $sf_data->getRaw('page');

sfContext::getInstance()->getResponse()->setTitle(htmlentities('Editing page template - ' . $sitetree->title, null, 'utf-8', false), false);

slot('breadcrumbs', get_partial('sitetree/breadcrumbs', array(
	'sitetree' => $sitetree
)));
?>

<div class="item_control">

	<h2>Edit template for '<?php echo $sitetree->getTitle(); ?>'</h2>
	  
    <?php echo include_partial('sitetree/sitetreeInfo', array('sitetree'=>$sitetree)); ?>
	
	<div class='sitetreeInfo'>
	    Current template is
	    <span class="site_sitetree_<?php if (!$sitetree->is_active) echo 'not_'; ?>published">
	      '<?php if ($page->template)
	      {
	        $defn = pageManager::getInstance()->getTemplateDefinition($page->template); 
	        echo $defn['name']; 
	      }
	      else echo 'Not set'; ?>'
	    </span>
	</div>
	
	<br />
	
	<p><?php echo __('Please choose a template:') ?></p>
	
	<p><span class="site_sitetree_not_published">WARNING:</span> Changing the template will delete the existing page content, unless the template contains the same fields</p>

	<?php if ($form->hasErrors()): ?>
	  <div class="ui-widget">
		<div class="ui-state-error ui-corner-all" style="margin: 10px; padding: 7px 0px 0px 7px;"> 
			<p><span class="ui-icon ui-icon-alert left"></span> 
			Please correct the following errors</p>
		</div>
	  </div>
	  <br />
	<?php endif; ?>

	<form method="post" action="">
		<fieldset class="fld_float">
		      <table>
		          <?php echo $form ?>
		      </table>
		</fieldset>
	
		<fieldset class="fld_submit">
		  <?php
		  $moduleDefinition = $sitetree->getModuleDefinition();
		  $url = $moduleDefinition['admin_url'] . "?routeName=$sitetree->route_name";
		  ?>
		   
		  <input type="submit" value="Save" class="btn_save frm_submit" />
		    
		  <?php echo button_to(__('Cancel'), $url, array('class'=>'btn_cancel frm_submit')); ?>
		  
		</fieldset>
	</form>
</div>