<?php
$item = $form->getObject();

$listingManager = listingManager::getInstance();
$defn = $listingManager->getTemplateDefinition($item->Listing->template); 

$isNew = (!$item->exists());
$moduleName = $sf_context->getModuleName();

$url = $moduleName . '/' . (!$isNew ? 'editItem?listId=' . $item->listing_id . '&id='.$item->id : 'createItem?id='.$item->listing_id);

sfContext::getInstance()->getResponse()->setTitle(htmlentities('Editing item - ' . $item->title . ' - ' . $sitetree->title, null, 'utf-8', false), false);

slot('breadcrumbs', get_partial('sitetree/breadcrumbs', array(
  'sitetree' => $sitetree
)));

use_javascripts_for_form($form);
use_stylesheets_for_form($form);
?>

<div id="sf_admin_container">

  <?php if ($isNew) : ?>
    <h1>Create new item for '<?php echo $sitetree->getTitle(); ?>'</h1>
  <?php else : ?>
    <h1><?php echo $sitetree->getTitle(); ?> - <?php echo $item->getTitle(); ?></h1>
  <?php endif; ?>
  
  <div id="sf_admin_header">
    <?php echo include_partial('sitetree/sitetreeInfo', array('sitetree'=>$sitetree, 'item'=>$item)); ?>
    
    <div class='sitetreeInfo'>
      Template is
      <span class="site_sitetree_<?php if (!$sitetree->is_active) echo 'not_'; ?>published">
        <?php echo $defn['name']; ?>
      </span>
      (<?php echo link_to('Back to listing', 'listingAdmin/edit?id=' . $item->listing_id); ?>)
    </div>
    
    <?php if (isset($defn['help'])) : ?>
      <div class='sitetreeInfo'>
        <h3><?php echo image_tag('/sfCmsPlugin/images/help.png', array('style'=>'vertical-align: top;')); ?> Template help</h3>
        <p><?php echo str_replace('%SITETREE%', $sitetree->getTitle(), $defn['help']); ?></p>
      </div>
    <?php endif;  ?>
  
  <?php if ($isNew): ?>
  
    <p>Please enter the details for your new list item below. Once it has been created you will be able to choose the images and descriptions.</p>
  </div>
  
  <div id="sf_admin_content">
    
    <div class="sf_admin_form">
  
      <?php if ($form->hasErrors()): ?>
        <div class="error">Please correct the following errors</div>
      <?php endif; ?>
    
        <?php echo $form->renderFormTag(''); ?>
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
             <li class="sf_admin_action_save"><input type="submit" value="Save" /></li>
             <li class="sf_admin_action_list"><?php echo link_to('Back to listing', 'listingAdmin/edit?id=' . $item->listing_id); ?></li>
           </ul>
        </form>
      </div>
    </div>
     
  <?php else: ?>
  
    <div class="sitetreeInfo">
      <div class="item-status">
        <span style="float:left;">This item is: &nbsp;</span>
        <?php if ($item->is_active): ?>
           <span class="site_sitetree_published" style="float:left;">Live</span>
        <?php else: //if ($item->is_active): ?>
          <span class="site_sitetree_not_published" style="float:left;">Not live</span>
        <?php endif; ?>
        <?php if ($item->is_hidden): ?>
           <span class="site_sitetree_hidden" style="float:left;">Hidden</span>
        <?php endif; ?>
        
        <?php if ($item->is_active): ?>
           <form method="post" action="" style="float:left; margin-top: -5px;">
              <input type="hidden" name="publish" value="0" />
              <input type="submit" value="Unpublish" />
           </form>
        <?php else: //if ($item->is_active): ?>
          <form method="post" action="" style="float:left;">
            <input type="hidden" name="publish" value="1" />
            <input type="submit" value="Publish" />
          </form>
        <?php endif; //if ($item->is_active): ?>
        <br class="clear" />
      </div>
    </div>
  </div>
  
  <div id="sf_admin_content">
    
    <div class="sf_admin_form">
  
      <script type="text/javascript">
        $(document).addEvent('domready', function() { 
          $(document).addEvent('domready', function() {
            new SimpleTabs('listing_item_<?php echo $item->slug; ?>_tabs', {
              selector: 'h4'
            });
          });
        });
      </script>
    
      <div id="listing_item_<?php echo $item->slug; ?>_tabs">
    
        <h4>Properties</h4>
        <div id='item_<?php echo $item->slug; ?>_properties'>
          <div class="content_border_normal">
    
          <?php if ($form->hasErrors()): ?>
            <div class="error">Please correct the following errors</div>
          <?php endif; ?>
    
          <?php if ($sf_user->hasFlash('notice')): ?>
            <div class="notice"><?php echo $sf_user->getFlash('notice'); ?></div>
          <?php endif; ?>
              
          <?php echo form_tag($url, array('multipart' => $form->isMultipart())); ?>
            <?php 
            echo $form->renderGlobalErrors(); 
            echo $form->renderHiddenFields();
            ?>
          
            <fieldset id="sf_fieldset_none">
              <?php $translations = array(); ?>
               <?php foreach ($item->Translation as $culture => $Translation) : ?>
                 <?php if (!empty($Translation->title)) $translations[$culture] = $Translation->title; ?>
              <?php endforeach; ?>
              <?php if (!empty($translations)) : ?>
                <div class="sf_admin_form_row">
                  <label>Item title translations</label>
                  <div class="content">
                    <?php foreach ($translations as $culture => $Translation) : ?>
                       <?php echo $culture . ' - ' . $Translation; ?><br />
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>
            
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
               <li class="sf_admin_action_save"><input type="submit" value="Save" /></li>
               <li class="sf_admin_action_list"><?php echo link_to('Back to listing', 'listingAdmin/edit?id=' . $item->listing_id); ?></li>
             </ul>
           </form>
  
          </div>
        </div>
    
        <h4>Content</h4>
        <div id='item_<?php echo $item->slug; ?>_content'>
      
          <div class="content_border_thin">
            <?php
            $url = 'listingAdmin/edit?id=' . $item->listing_id;
            include_component('contentAdmin', 'editor', array('contentGroup' => $contentGroup, 'cancelUrl'=>$url, 'formTarget'=>'#item_'. $item->slug.'_content'));
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>
      
  <?php endif; ?>

</div>