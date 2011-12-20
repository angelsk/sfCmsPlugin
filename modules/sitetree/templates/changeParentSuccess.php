<?php
$sitetree = $form->getObject();

slot('breadcrumbs', get_partial('sitetree/breadcrumbs', array('sitetree' => $sitetree, 'editNode' => true)));

$isNew = !($sitetree->id > 0);
$culture = $sf_user->getCulture();

use_javascripts_for_form($form);
use_stylesheets_for_form($form);
?>

<div id="sf_admin_container">

  <h1>Change parent of '<?php echo $sitetree->getTitle(); ?>'</h1>

  <div id="sf_admin_header">
    <p>This allows you to reposition this page within the sitetree (it will be added as the last child of the selected parent).</p>
  </div>
  
  <?php if ($form->hasErrors()) : ?>
    <div class="error">The sitetree node has not been saved due to some errors.</div>
  <?php endif; ?>

  <div id="sf_admin_content">
    <div class="sf_admin_form">

      <?php echo $form->renderFormTag(url_for('sitetree/changeParent?id='.$sitetree->getId())); ?>
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
          <li class="sf_admin_action_list"><?php echo link_to('Back to tree', 'sitetree/index'); ?></li>
          <li class="sf_admin_action_save"><input type="submit" value="Save"></li>
        </ul>
      </form>
    </div>
  </div>
</div>