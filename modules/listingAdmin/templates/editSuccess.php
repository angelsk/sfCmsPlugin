<?php
use_helper("sfDoctrineSuperPager");

$pagerAjaxUrl = "listingAdmin/listItemsAjax?id=$listing->id";
$url = "listingAdmin/edit?id=$listing->id";
$pager = $sf_data->getRaw('pager');
$contentGroup = $sf_data->getRaw('contentGroup');
$sitetree = $sf_data->getRaw('sitetree');

$listingManager = listingManager::getInstance();
$defn = $listingManager->getTemplateDefinition($listing->template); 

sfContext::getInstance()->getResponse()->setTitle(htmlentities('Editing page' . ' - ' . $sitetree->title, null, 'utf-8', false), false);

slot('breadcrumbs', get_partial('sitetree/breadcrumbs', array('sitetree' => $sitetree)));
?>

<div id="sf_admin_container">

  <h1><?php echo $sitetree->getTitle(); ?></h1>

  <div id="sf_admin_header">  
    <?php echo include_partial('sitetree/sitetreeInfo', array('sitetree'=>$sitetree)); ?>
    
    <div class='sitetreeInfo'>
      Template is
      <span class="site_sitetree_<?php if (!$sitetree->is_active) echo 'not_'; ?>published">
        <?php echo $defn['name']; ?>
      </span>
      (change template on Properties tab)
    </div>
  </div>
  
  <script type="text/javascript">
    $(document).addEvent('domready', function() {
      new SimpleTabs('listing_<?php echo $sitetree->route_name; ?>_tabs', {
        selector: 'h4'
      });
    });  
  </script>
  
  <div id="sf_admin_content">
  
    <div id="listing_<?php echo $sitetree->route_name; ?>_tabs">
  
      <h4>Items</h4>
      
      <div id='listing_<?php echo $sitetree->route_name; ?>_items'>
        <?php if ($sf_user->hasFlash('listing_notice')): ?>
          <div class="notice"><?php echo $sf_user->getFlash('listing_notice'); ?></div>
        <?php endif; ?>
            
        <p>These are the items in our list.  The ordering here is the same as the ordering used on the frontend of the site (<?php echo $listing->use_custom_order ? 'manually' : $listingManager->getListItemOrdering($listing->template); ?>).</p>
      
        <?php echo super_pager_render($pager, $url, $pagerAjaxUrl); ?>
          
        <?php if (0 == $pager->getNbResults() && (!isset($defn['use_categories']) || true === $defn['use_categories'])) : ?>
          <p><span class="site_sitetree_not_published">NOTE:</span>Make sure you set up your categories on the Categories tab before adding an item.</p>
        <?php endif; ?>
      
        <ul class="sf_admin_actions">
          <li class="sf_admin_action_new">
            <?php echo link_to('Create new item', 'listingAdmin/createItem?id=' . $listing->id, array('class' => 'btn_create float_r frm_submit')); ?>
          </li>
        </ul>
          
        <script type="text/javascript">
          $(document).addEvent('domready', function() {
            $$('.btn_remove').each(function(el) { 
              el.addEvent('click', function() {
                return confirm('Are you sure you want to delete this item - it cannot be undone');
           		 }); 
            }); 
          });
        </script>
      </div>
      
      <?php if (!isset($defn['use_categories']) || true === $defn['use_categories']) : ?>
        <h4>Categories</h4>
        
        <div id="listing_<?php echo $sitetree->route_name; ?>_categories">
          <?php
          echo include_component('listingAdmin', 'categoryEditor', array('listing' => $listing, 'formTarget' => 'listing_'.$sitetree->route_name.'_categories'));
          ?>
        </div>
      <?php endif ?>
    
      <h4>Content</h4>
      
      <div id="listing_<?php echo $sitetree->route_name; ?>_content">
        <?php
        $url = 'sitetree/index';
        echo include_component('contentAdmin', 'editor', array('contentGroup' => $contentGroup, 'cancelUrl'=>$url, 'formTarget'=>'#listing_'.$sitetree->route_name.'_content'));
        ?>
      </div>
    
      <h4>Properties</h4>
      
      <div id="listing_<?php echo $sitetree->route_name; ?>_properties">
        <div class="content_border_thin">
          <?php if ($sitetree->is_locked) : ?>
             <p>Cannot edit properties of a locked page</p>
          <?php else: ?>
            <?php if ($form->hasErrors()): ?>
              <div class="error">Please correct the following errors</div>
            <?php endif; ?>
            <?php if ($sf_user->hasFlash('edit_notice')): ?>
              <div class="notice"><?php echo $sf_user->getFlash('edit_notice'); ?></div>
            <?php endif; ?>
              
            <p><span class="site_sitetree_not_published">WARNING:</span> Changing the template will delete the existing page content, unless the template contains the same fields.</p>
                  
            <form method="post" action="#listing_<?php echo $sitetree->route_name; ?>_properties">
              <?php 
              echo $form->renderGlobalErrors(); 
              echo $form->renderHiddenFields();
              ?>
            
              <fieldset id="sf_fieldset_none">
                <?php foreach ($form as $idx => $widget):
                  if (!$widget->isHidden()) : ?>
                  
                    <div class="sf_admin_form_row <?php if ($widget->hasError()) echo 'errors'; ?>">
                      <?php echo $widget->renderError(); ?>
                      <div>
                        <?php echo $widget->renderLabel(); ?>
                        <div class="content"><?php echo $widget->render(); ?></div>
                        <?php if ($help = $widget->renderHelp()) : ?><div class="help"><?php echo str_replace('<br />', '', $help); ?></div><?php endif; ?>
                      </div>
                    </div>
                      
                  <?php endif; 
                endforeach; ?>
              </fieldset>
              
              <ul class="sf_admin_actions">
                <li class="sf_admin_action_save"><input type="submit" class="btn_save float_r frm_submit" value="Save"  /></li>
                <li class="sf_admin_action_list"><?php echo link_to('Back to sitetree', 'sitetree/index'); ?></li>
              </ul>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
