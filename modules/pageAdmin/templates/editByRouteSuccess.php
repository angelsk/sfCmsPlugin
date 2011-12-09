<?php
$sitetree = $sf_data->getRaw('sitetree');

slot('breadcrumbs', get_partial('sitetree/breadcrumbs', array(
  'sitetree' => $sitetree
)));
?>

<div id="sf_admin_container">

  <h1><?php echo $sitetree->getTitle(); ?></h1>
  
  <div id="sf_admin_header">
    <?php echo include_partial('sitetree/sitetreeInfo', array('sitetree'=>$sitetree)); ?>
    
    <p>Please choose a template for your new page:</p>
  </div>

  <?php if ($form->hasErrors()) : ?>
    <div class="error">The page has not been saved due to some errors.</div>
  <?php endif; ?>
  
  <?php if ($sf_user->hasFlash('notice')) : ?>
    <div class="notice"><?php echo $sf_user->getFlash('notice'); ?></div>
  <?php endif; ?>
  
  <div id="sf_admin_content">
    
    <div class="sf_admin_form">

      <?php echo $form->renderFormTag(url_for(sprintf('pageAdmin/editByRoute?routeName=%s&site=%s', $routeName, $site))); ?>
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
        </ul>
      </form>
    </div>
  
  </div>
</div>