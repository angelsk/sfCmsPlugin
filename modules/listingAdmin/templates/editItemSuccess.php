<?php
$item = $form->getObject();
$contentGroup = (isset($contentGroup) ? $sf_data->getRaw('contentGroup') : null);
$sitetree = $sf_data->getRaw('sitetree');

$isNew = (!$item->exists());
$moduleName = $sf_context->getModuleName();

$url = $moduleName . '/' . (!$isNew ? 'editItem?listId=' . $item->listing_id . '&id='.$item->id : 'createItem?id='.$item->listing_id);

sfContext::getInstance()->getResponse()->setTitle(htmlentities('Editing item' . ' - ' . $sitetree->title . ' - ' . $item->title, null, 'utf-8', false), false);

slot('breadcrumbs', get_partial('sitetree/breadcrumbs', array(
	'sitetree' => $sitetree
)));
?>

<div class="item_control">

  <?php if ($isNew) : ?>
  	<h2>Create new item for '<?php echo $sitetree->getTitle(); ?>'</h2>
  <?php else : ?>
  	<h2><?php echo $sitetree->getTitle(); ?> - <?php echo $item->getTitle(); ?></h2>
  <?php endif; ?>
  
  <?php echo include_partial('sitetree/sitetreeInfo', array('sitetree'=>$sitetree, 'item'=>$item)); ?>

	<?php if ($isNew): ?>
	
	    <p>Please enter the details for your new list item below. Once it has been created you will be able to choose the images and descriptions.</p>
	
		<?php if ($form->hasErrors()): ?>
		  <div class="ui-widget">
			<div class="ui-state-error ui-corner-all" style="margin: 10px; padding: 7px 0px 0px 7px;"> 
				<p><span class="ui-icon ui-icon-alert left"></span> 
				Please correct the following errors</p>
			</div>
		  </div>
		  <br />
		<?php endif; ?>
	
	    <?php echo $form->renderFormTag(''); ?>
	       <fieldset class="fld_float">
	          <table>
	             <tbody>
	                <?php echo $form ?>
	             </tbody>
	           </table>
	
	           <input type="submit" class="btn_save float_r frm_submit" value="Save"  />
	       </fieldset>
	    </form>
	   
	<?php else: ?>
	
	  <div class="sitetreeInfo">
		  
		  <div class="item-status">
		    <span class="left">This item is: &nbsp;</span>
		    <?php if ($item->is_active): ?>
		       <span class="site_sitetree_published float_l">Live</span>
		    <?php else: //if ($item->is_active): ?>
		      <span class="site_sitetree_not_published float_l">Not live</span>
		    <?php endif; ?>
		    <?php if ($item->is_hidden): ?>
		       <span class="site_sitetree_hidden float_l">Hidden</span>
		    <?php endif; ?>
		    
		    <?php echo link_to('[back to listing page]', 'listingAdmin/edit?id=' . $item->listing_id) ?>
		    <br class="clear" /><br />
		    
		    <?php if ($item->is_active): ?>
		       <form method="post" action="" class="float_l">
		          <input type="hidden" name="publish" value="0" />
		          <input type="submit" value="Unpublish" class="btn_cancel float_l frm_submit" />
		       </form>
		    <?php else: //if ($item->is_active): ?>
		      <form method="post" action="" class="float_l">
		      	<input type="hidden" name="publish" value="1" />
		        <input type="submit" class="btn_publish float_l frm_submit" value="Publish" />
		      </form>
		    <?php endif; //if ($item->is_active): ?>
		    
		    <br class="clear" />
		  </div>
	  </div>
	  <br class="clear" /><br />
	
	  <script type="text/javascript">
	  	$(document).ready( function() { $('#listing_item_<?php echo $item->slug; ?>_tabs').tabs(); } );
	  </script>
	
	  <div id="listing_item_<?php echo $item->slug; ?>_tabs">
	    <ul>
	       <li><a href="#item_<?php echo $item->slug; ?>_properties">Properties</a></li>
	   	   <li><a href="#item_<?php echo $item->slug; ?>_content">Content</a></li>
	    </ul>
	
	    <div id='item_<?php echo $item->slug; ?>_properties'>
	      <div class="content_border_normal">
	
			<?php if ($form->hasErrors()): ?>
			  <div class="ui-widget">
				<div class="ui-state-error ui-corner-all" style="margin: 10px; padding: 7px 0px 0px 7px;"> 
					<p><span class="ui-icon ui-icon-alert left"></span> 
					Please correct the following errors</p>
				</div>
			  </div>
			  <br />
			<?php endif; ?>

            <?php if ($sf_user->hasFlash('notice')): ?>
			  <div class="ui-widget">
				 <div class="ui-state-highlight ui-corner-all" style="margin: 10px; padding: 7px 0px 0px 7px;"> 
					<p><span class="ui-icon ui-icon-info left"></span>
					<?php echo $sf_user->getFlash('notice'); ?></p>
				 </div>
			  </div>
			  <br />
			<?php endif; ?>
				    
			<?php echo form_tag($url, array('multipart' => $form->isMultipart())); ?>
	          <fieldset class="fld_float">
	             <table>
	                <tbody>
	                   <?php $translations = array(); ?>
	                   <?php foreach ($item->Translation as $culture => $Translation) : ?>
                           <?php if (!empty($Translation->title)) $translations[$culture] = $Translation->title; ?>
                       <?php endforeach; ?>
                       <?php if (!empty($translations)) : ?>
	                      <tr>
	                        <td>Item title translations</td>
				            <td>
				              <?php foreach ($translations as $culture => $Translation) : ?>
				                 <?php echo $culture . ' - ' . $Translation; ?><br />
				              <?php endforeach; ?>
				            </td>
				          </tr>
				       <?php endif; ?>
				                
				       <?php echo $form ?>
				    </tbody>
				  </table>
			    </fieldset>
				<fieldset class="fld_submit">
					<input type="submit" class="btn_save frm_submit" value="Save"  />
			                  
				    <?php echo button_to('Cancel', 'listingAdmin/edit?id=' . $item->listing_id, array('class'=>'btn_cancel frm_submit')); ?>
				</fieldset>
			 </form>
	
		  </div>
		</div>
	
		<div id='item_<?php echo $item->slug; ?>_content'>
	
		  <div class="content_border_thin">
		   	 <?php
		     $url = 'listingAdmin/edit?id=' . $item->listing_id;
		     include_component('contentAdmin', 'editor', array('contentGroup' => $contentGroup, 'cancelUrl'=>$url, 'formTarget'=>'#item_'. $item->slug.'_content'));
		     ?>
		  </div>
	    </div>
	  </div>
	  
	<?php endif; ?>

</div>