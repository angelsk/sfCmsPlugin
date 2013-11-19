<?php
sfContext::getInstance()->getResponse()->setTitle(htmlentities('Editing redirect' . ' - ' . $sitetree->title, null, 'utf-8', false), false);

slot('breadcrumbs', get_partial('sitetree/breadcrumbs', array(
  'sitetree' => $sitetree
)));
?>

<div id="sf_admin_container">

  <h1><?php echo $sitetree->getTitle(); ?></h1>
  
  <div id="sf_admin_header">
    <?php echo include_partial('sitetree/sitetreeInfo', array('sitetree'=>$sitetree)); ?>
  </div>
  
  <?php if ($form->hasErrors()) : ?>
    <div class="error">Please correct the following errors: <?php echo $form->renderGlobalErrors(); ?></div>
  <?php endif; ?>
  
  <?php if ($sf_user->hasFlash('notice')) : ?>
    <div class="notice"><?php echo $sf_user->getFlash('notice'); ?></div>
  <?php endif; ?>
  
  <div id="sf_admin_content">
    <div class="sf_admin_form">
      <?php echo $form->renderFormTag(url_for(sprintf('redirectAdmin/edit?id=%s', $form->getObject()->id))); ?>
        <?php echo $form->renderHiddenFields(); ?>
      
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
        
        <?php if ($canPublish) : ?>
          <ul class="sf_admin_actions">
            <li class="sf_admin_action_save"><input type="submit" value="Save"></li>
          </ul>
        <?php else : ?>
          <p>Sorry, you need publish permissions to be able to create/ edit a redirect</p>
        <?php endif; ?>
      </form>
    </div>
  </div>
</div>
