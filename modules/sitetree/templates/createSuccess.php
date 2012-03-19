<?php
slot('breadcrumbs', get_partial('sitetree/breadcrumbs', array(
  'breadcrumbs' => array(link_to('Sitetree', 'sitetree/index'))
)));

$culture = $sf_user->getCulture();
use_javascripts_for_form($form);
use_stylesheets_for_form($form);
?>

<div id="sf_admin_container">

  <h1>Create new page</h1>

  <div id="sf_admin_header">
    <div class="sitetreeInfo">
      <p>Creating as a child of: 
        <?php 
        if ($parent->is_hidden) $class = 'site_sitetree_hidden';
        else if ($parent->is_active) $class = 'site_sitetree_published';
        else $class = 'site_sitetree_not_published';
        ?>
        <span class="<?php echo $class; ?>"><?php echo $parent->getTitle(); ?></span>
      </p>
    </div>
  
    <?php if (!$canAdmin) 
    {
      echo '<p class="error site_notice">You are not a sitetree administrator, so you cannot lock or unlock nodes.</p>';
    } ?>
  </div>

  <?php if ($form->hasErrors()) : ?>
    <div class="error">The sitetree node has not been saved due to some errors.</div>
  <?php endif; ?>

  <div id="sf_admin_content">
    <div class="sf_admin_form">

      <?php echo $form->renderFormTag(url_for('sitetree/create?parent=' . $parentId)); ?>
        <?php 
        echo $form->renderGlobalErrors(); 
        echo $form->renderHiddenFields();
        ?>
      
        <fieldset id="sf_fieldset_none">
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

<script type="text/javascript">
  var culture = '<?php echo $culture; ?>';
</script>