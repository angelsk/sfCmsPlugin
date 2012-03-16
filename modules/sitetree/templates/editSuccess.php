<?php
$sitetree = $form->getObject();

slot('breadcrumbs', get_partial('sitetree/breadcrumbs', array('sitetree' => $sitetree, 'editNode' => true)));

$isNew = !($sitetree->id > 0);
$culture = $sf_user->getCulture();

use_javascripts_for_form($form);
use_stylesheets_for_form($form);
?>

<div id="sf_admin_container">

  <h1>Edit '<?php echo $sitetree->getTitle(); ?>' properties</h1>

  <div id="sf_admin_header">
    <?php echo include_partial('sitetree/sitetreeInfo', array('sitetree'=>$sitetree)); ?>
  
    <?php if (!$sf_user->hasCredential('site.admin')) 
    {
      echo '<p class="error site_notice">You are not an administrator, so you cannot lock or unlock nodes</p>';
    }
    if (!$sf_user->hasCredential(array('site.admin', 'site.publish')) && !$sitetree->is_active) 
    {
      echo '<p class="error site_notice">You are not a publisher, so you cannot put this page live</p>';
    } ?>
  </div>
  
  <?php if ($form->hasErrors()) : ?>
    <div class="error">The sitetree node has not been saved due to some errors.</div>
  <?php endif; ?>

  <div id="sf_admin_content">
    <div class="sf_admin_form">

      <?php echo $form->renderFormTag(url_for('sitetree/edit'.(!$isNew ? '?id='.$sitetree->getId() : ''))); ?>
        <?php 
        echo $form->renderGlobalErrors(); 
        echo $form->renderHiddenFields();
        ?>
      
        <fieldset id="sf_fieldset_none">
          <div class="sf_admin_form_row">
            <div>
              <label>Unique identifier</label>
              <div class="content"><strong><?php echo $sitetree->route_name ?></strong></div>
            </div>
          </div>
          
          <?php $translations = array(); ?>
          <?php foreach ($form->getObject()->Translation as $culture => $Translation) : ?>
            <?php if (!empty($Translation->title)) $translations[$culture] = $Translation->title; ?>
          <?php endforeach; ?>
          <?php if (!empty($translations)) : ?>
            <div class="sf_admin_form_row">
              <label>Sitetree title translations</label>
              <div class="content">
                <?php foreach ($translations as $culture => $Translation) : ?>
                   <?php echo $culture . ' - ' . $Translation; ?><br />
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
          
          <?php foreach ($form as $idx => $widget):
            if (!$widget->isHidden()) : ?>
              <?php if ($culture == $idx) : // embedded translation form ?>
              
                <?php foreach ($widget as $idx2 => $tWidget) : 
                  if (!$tWidget->isHidden()) : ?>
              
                    <div class="sf_admin_form_row <?php if ($tWidget->hasError()) echo 'errors'; ?>">
                      <?php echo $tWidget->renderError(); ?>
                      <div>
                        <?php echo $tWidget->renderLabel(); ?>
                        <div class="content"><?php echo $tWidget->render(); ?></div>
                        <?php if ($help = $tWidget->renderHelp()) : ?><div class="help"><?php echo str_replace('<br />', '', $help); ?></div><?php endif; ?>
                      </div>
                    </div>
                
                  <?php endif;
                endforeach; ?>
              
              <?php else : ?>
            
                <div class="sf_admin_form_row <?php if ($widget->hasError()) echo 'errors'; ?>">
                  <?php echo $widget->renderError(); ?>
                  <div>
                    <?php echo $widget->renderLabel(); ?>
                    <div class="content"><?php echo $widget->render(); ?></div>
                    <?php if ($help = $widget->renderHelp()) : ?><div class="help"><?php echo str_replace('<br />', '', $help); ?></div><?php endif; ?>
                  </div>
                </div>
                
              <?php endif;
            endif; 
          endforeach; ?>
        </fieldset>
        
        <ul class="sf_admin_actions">
          <li class="sf_admin_action_list"><?php echo link_to('Back to list', 'sitetree/index'); ?></li>
          <li class="sf_admin_action_save"><input type="submit" value="Save"></li>
        </ul>
      </form>
    </div>
  </div>
</div>