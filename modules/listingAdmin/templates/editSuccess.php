<?php
use_helper("sfDoctrineSuperPager");

$pagerAjaxUrl = "listingAdmin/listItemsAjax?id=$listing->id";
$url = "listingAdmin/edit?id=$listing->id";
$pager = $sf_data->getRaw('pager');
$contentGroup = $sf_data->getRaw('contentGroup');
$sitetree = $sf_data->getRaw('sitetree');

$listingManager = listingManager::getInstance();
$defn = $listingManager->getTypeDefinition($listing->type); 

sfContext::getInstance()->getResponse()->setTitle(htmlentities('Editing page' . ' - ' . $sitetree->title, null, 'utf-8', false), false);

slot('breadcrumbs', get_partial('sitetree/breadcrumbs', array('sitetree' => $sitetree)));
?>

<div class="item_control">

  <h2><?php echo $sitetree->getTitle(); ?></h2>
  
  <?php echo include_partial('sitetree/sitetreeInfo', array('sitetree'=>$sitetree)); ?>
  
  <div class='sitetreeInfo'>
	  Template is
      <span class="site_sitetree_<?php if (!$sitetree->is_active) echo 'not_'; ?>published">
      	<?php echo $defn['name']; ?>
      </span>
      
      [change template on Properties tab]
  </div>
  <br />
  
  <script type="text/javascript">
  	$(document).ready(function() { $('#listing_<?php echo $sitetree->route_name; ?>_tabs').tabs(); });	
  </script>
  
  <div id="listing_<?php echo $sitetree->route_name; ?>_tabs">

	<ul>
	    <li><a href='#listing_<?php echo $sitetree->route_name; ?>_items'>Items</a></li>
	    <?php if (!isset($defn['use_categories']) || true === $defn['use_categories']) : ?>
	        <li><a href='#listing_<?php echo $sitetree->route_name; ?>_categories'>Categories</a></li>
	    <?php endif; ?>
		<li><a href='#listing_<?php echo $sitetree->route_name; ?>_content'>Content</a></li>
		<li><a href='#listing_<?php echo $sitetree->route_name; ?>_properties'>Properties</a></li>
	</ul>
	
	<div id='listing_<?php echo $sitetree->route_name; ?>_items'>
	    <div id="sf_admin_container">
	    	<?php if ($sf_user->hasFlash('listing_notice')): ?>
			  <div class="ui-widget">
				 <div class="ui-state-highlight ui-corner-all" style="margin: 10px; padding: 7px 0px 0px 7px;"> 
					<p><span class="ui-icon ui-icon-info left"></span>
					<?php echo $sf_user->getFlash('listing_notice'); ?></p>
				 </div>
			  </div>
			<?php endif; ?>
				
	        <p>These are the items in our list.  The ordering here is the same as the ordering used on the frontend of the site (<?php echo $listingManager->getListItemOrdering($listing->type); ?>).</p>
	
			<?php echo super_pager_render($pager, $url, $pagerAjaxUrl); ?>
			
			<?php if (0 == $pager->getNbResults() && (!isset($defn['use_categories']) || true === $defn['use_categories'])) : ?>
				<p><span class="site_sitetree_not_published">NOTE:</span>Make sure you set up your categories on the Categories tab before adding an item.</p>
			<?php endif; ?>
	
		    <ul class="sf_admin_actions">
		      <li class="sf_admin_action_new">
		        <?php echo link_to('Create new item', 'listingAdmin/createItem?id=' . $listing->id, array('class' => 'btn_create float_r frm_submit')); ?>
		      </li>
		    </ul>
	    </div>
	    
	    <script type="text/javascript">
	    	$(document).ready( function() {
	    	    $('.btn_remove').click( function() {
		    	    return confirm('Are you sure you want to delete this item - it cannot be undone');
		    	} );	
	    	});
	    </script>
	</div>
	
	<?php if (!isset($defn['use_categories']) || true === $defn['use_categories']) : ?>
	    <div id='listing_<?php echo $sitetree->route_name; ?>_categories'>
	    	<div class="content_border_thin">
	            <?php
	        	echo include_component('listingAdmin', 'categoryEditor', array('listing' => $listing, 'formTarget' => 'listing_'.$sitetree->route_name.'_categories'));
	            ?>
	  		</div>
		</div>
	<?php endif ?>

	<div id='listing_<?php echo $sitetree->route_name; ?>_content'>
	    <div class="content_border_thin">
	        <?php
	        $url = 'sitetree/index';
	        echo include_component('contentAdmin', 'editor', array('contentGroup' => $contentGroup, 'cancelUrl'=>$url, 'formTarget'=>'#listing_'.$sitetree->route_name.'_content'));
	        ?>
	    </div>
	</div>

	<div id='listing_<?php echo $sitetree->route_name; ?>_properties'>
	    <div class="content_border_thin">
	        <?php if ($sitetree->is_locked) : ?>
	        
	          <p>Cannot edit properties of a locked page</p>
	          
	        <?php else: ?>
	        	<?php if ($form->hasErrors()): ?>
				  <div class="ui-widget">
					<div class="ui-state-error ui-corner-all" style="margin: 10px; padding: 7px 0px 0px 7px;"> 
						<p><span class="ui-icon ui-icon-alert left"></span> 
						Please correct the following errors</p>
					</div>
				  </div>
				  <br />
				<?php endif; ?>
	
	            <?php if ($sf_user->hasFlash('edit_notice')): ?>
				  <div class="ui-widget">
					 <div class="ui-state-highlight ui-corner-all" style="margin: 10px; padding: 7px 0px 0px 7px;"> 
						<p><span class="ui-icon ui-icon-info left"></span>
						<?php echo $sf_user->getFlash('edit_notice'); ?></p>
					 </div>
				  </div>
				<?php endif; ?>
	        
	            <p><span class="site_sitetree_not_published">WARNING:</span> Changing the template will delete the existing page content, unless the template contains the same fields.</p>
	            
	            <form method="post" action="#listing_<?php echo $sitetree->route_name; ?>_properties">
		            <fieldset id="editors" class="fld_float">
		                <table id="editor_table" >
		                    <?php echo $form ?>
		                </table>

		                <input type="submit" class="btn_save float_r frm_submit" value="Save"  />
		                <?php echo button_to('Cancel', 'sitetree/index', array('class'=>'btn_cancel float_r frm_submit')); ?>
		            </fieldset>
		         </form>
	        <?php endif; ?>
	    </div>
	</div>
  </div>
</div>
