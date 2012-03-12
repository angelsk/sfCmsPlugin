<?php
$listingManager = listingManager::getInstance();
$defn = $listingManager->getTemplateDefinition($listing->template); 
$activeSites = siteManager::getInstance()->getActiveSites();

sfContext::getInstance()->getResponse()->setTitle(htmlentities('Importing items to - ' . $sitetree->title, null, 'utf-8', false), false);

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
      (<?php echo link_to('Back to listing', 'listingAdmin/edit?id=' . $listing->id); ?>)
    </div>
    
    <?php if (isset($defn['help'])) : ?>
      <div class='sitetreeInfo'>
        <h3><?php echo image_tag('/sfCmsPlugin/images/help.png', array('style'=>'vertical-align: top;')); ?> Template help</h3>
        <p><?php echo str_replace('%SITETREE%', $sitetree->getTitle(), $defn['help']); ?></p>
      </div>
    <?php endif;  ?>
  </div>
  
  <div id="sf_admin_content">
    
    <?php if ($sf_user->hasFlash('listing_notice')): ?>
      <div class="notice"><?php echo $sf_user->getFlash('listing_notice'); ?></div>
    <?php endif; ?>
    
    <?php if ($sf_user->hasFlash('listing_error')): ?>
      <div class="error"><?php echo $sf_user->getFlash('listing_error'); ?></div>
    <?php endif; ?>
  
    <div class="sf_admin_form">
    
      <form method="post">
  
        <fieldset>
          
          <h2>Import items</h2>

          <div class="sf_admin_form_row sf_admin_text sf_admin_form_field_import_listing_items">
            <label for="import_listing_items[">Select the items you want to import:</label>
              
            <div class="content">
              <select multiple="multiple" name="import_listing_items[]" class="wide">
                 
                <?php foreach ($items as $item) : ?>
                  <option value="<?php echo $item->id; ?>"<?php if (in_array($item->id, $importedItems)) echo ' selected="selected"'; ?>>
                    <?php echo $item->Listing->Sitetree->getTitle(); ?> (<?php echo $activeSites[$item->Listing->Sitetree->site]; ?>) - <?php echo $item->getTitle(); ?>
                    <?php if ($item->item_date) : ?>
                      (<?php echo $item->getDateTimeObject('item_date')->format('d M Y'); ?>)
                    <?php endif; ?>
                  </option>
                <?php endforeach; ?>
                                      
              </select>
            </div>
            <div class="help"><br />Hold down CTRL (PC) / CMD (Mac) to select multiple items.
              <br />The ordering here is <strong><?php echo $listing->use_custom_order ? 'manually' : $listingManager->getListItemOrdering($listing->template); ?></strong></div>
          </div>
            
        </fieldset>
          
        <ul class="sf_admin_actions">
          <li class="sf_admin_action_save"><input type="submit" value="Import" /></li>
          <li class="sf_admin_action_list"><?php echo link_to('Back to listing', 'listingAdmin/edit?id=' . $listing->id); ?></li>
        </ul>
        
      </form>
      
    </div>
  </div>
</div>
