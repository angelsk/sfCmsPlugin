<?php
$sitetree = $sf_data->getRaw('sitetree');
$page = $sf_data->getRaw('page');

sfContext::getInstance()->getResponse()->setTitle(htmlentities('Editing page template - ' . $sitetree->title, null, 'utf-8', false), false);

slot('breadcrumbs', get_partial('sitetree/breadcrumbs', array(
  'sitetree' => $sitetree
)));
?>

<div id="sf_admin_container">

  <h1>Edit template for <?php echo $sitetree->getTitle(); ?></h1>
    
  <div id="sf_admin_header">
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
    
    <p><?php echo __('Please choose a template:') ?></p>
    
    <p><span class="site_sitetree_not_published">WARNING:</span> Changing the template will delete the existing page content, unless the template contains the same fields</p>
  </div>
  
  <?php if ($form->hasErrors()) : ?>
    <div class="error">Please correct the following errors</div>
  <?php endif; ?>

  <div id="sf_admin_content">
    
    <div class="sf_admin_form">
    
      <?php echo $form->renderFormTag(url_for(sprintf('pageAdmin/editTemplate?id=%s', $sf_params->get('id')))); ?>
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
          <li class="sf_admin_action_save"><input type="submit" value="Save"></li>
          
          <?php
          $moduleDefinition = $sitetree->getModuleDefinition();
          $url = $moduleDefinition['admin_url'] . "?routeName=$sitetree->route_name&site=$sitetree->site";
          ?>
           
          <li class="sf_admin_action_list"><?php echo link_to(__('Back to page'), $url); ?></li>
        </ul>
      </form>
    </div>
  </div>
</div>