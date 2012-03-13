<?php
$contentGroup = $sf_data->getRaw('contentGroup');
$contentBlock = $sf_data->getRaw('contentBlock');
$sf_request = $sf_data->getRaw('sf_request');

// this is the version we're editing now:
$contentBlockVersion = $sf_data->getRaw('contentBlockVersion');

// this is the currently live version:
$currentContentBlockVersion = $contentBlock->getCurrentVersion();

// any errors trying to edit this version:
$errors = (isset($errors) ? $sf_data->getRaw('errors') : array());
$isThisLiveVersion = ($contentBlockVersion->id == $currentContentBlockVersion->id);

$versionHistoryArray = $contentBlock->getEfficientVersionHistoryWithUsers($contentBlockVersion->lang);
$formTarget = ($sf_data->offsetExists('formTarget') ? $sf_data->getRaw('formTarget') : '');
?>

<div class="content_block_editor_item">

<h3><?php echo esc_entities($contentBlock->getDefinitionParam('name')) ?></h3>

<?php if (count($errors) > 0):?>
    <ul class="content_block_editor_item_errors">
        <?php foreach ($errors as $error): ?>
            <li><?php echo esc_entities($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php $content = get_slot('cms_js');  ?>
<?php slot('cms_js');
  if (sfConfig::get('app_site_use_slots', false)) echo $content; // If using slot, combine them ?>
  
  <script type="text/javascript">
    $(document).addEvent('domready', function () 
    { 
      new SimpleTabs('content_block_tabs_<?php echo $contentBlock->identifier; ?>', 
      {
        selector: 'h5'
      });
    });
  </script>
<?php end_slot(); ?>
<?php if (!sfConfig::get('app_site_use_slots', false)) include_slot('cms_js'); ?>

<div id="content_block_tabs_<?php echo $contentBlock->identifier; ?>">
  <div class="section">
    <h5>Edit</h5>
    <div id="edit_<?php echo $contentBlock->identifier; ?>">
      <div class="content_block_editor_control" style="width: 78%; float: left;">
        <?php $form = $contentBlockVersion->getContentBlockType()->editRender($sf_request); ?>
          
        <?php 
        echo $form->renderGlobalErrors(); 
        echo $form->renderHiddenFields();
        ?>
      
        <?php foreach ($form as $idx => $widget):
          if (!$widget->isHidden()) : ?>
          
            <div class="sf_admin_form_row <?php if ($widget->hasError()) echo 'errors'; ?>">
              <?php echo $widget->renderError(); ?>
              <div>
                <?php $label = $widget->renderLabel(); 
                $rawLabel = trim(strip_tags($label)); 
                if (!empty($rawLabel) && '&nbsp;' != $rawLabel) echo $label; ?>
                <div class="content<?php if (empty($rawLabel) || '&nbsp;' == $rawLabel) echo ' no_label'; ?>"><?php echo $widget->render(); ?></div>
                <?php if ($help = $widget->renderHelp()) : ?><div class="help"><?php echo str_replace('<br />', '', $help); ?></div><?php endif; ?>
              </div>
            </div>
              
          <?php endif; 
        endforeach; ?>
      </div>
      
      <div class="content_block_editor_messages" style="width: 20%; float: right;">
        <?php if ($contentBlock->getDefinitionParam('help')) : ?>
          <p><?php echo image_tag('/sfCmsPlugin/images/help.png'); ?> <?php echo $contentBlock->getDefinitionParam('help'); ?></p>
        <?php endif; ?>
        <?php if ('HTML' == $contentBlock->getDefinitionParam('type')) : ?>
          <p><?php echo image_tag('/sfCmsPlugin/images/help.png'); ?> Use SHIFT + RETURN to create a soft new line (&lt;br />) and RETURN to create a new paragraph.</p>
        <?php endif; ?>

        <?php if ($contentBlock->useLang()): ?>
          <p><?php echo image_tag('/sfCmsPlugin/images/information.png'); ?> This has different versions for each language</p>
        <?php else: ?>
          <p><?php echo image_tag('/sfCmsPlugin/images/information.png'); ?> This is shared between all languages</p>
        <?php endif; ?>

        <?php if (!$contentBlockVersion->isCurrent()): ?>
          <p><?php echo image_tag('/sfCmsPlugin/images/warning.png'); ?> This version is not live</p>
        <?php endif; ?>

        <?php if (!$contentBlockVersion->isNewest()): ?>
          <p><?php echo image_tag('/sfCmsPlugin/images/warning.png'); ?> There is a newer version of this content</p>
        <?php endif; ?>
      </div>
      
      <br style="clear:both" />
    </div>
  
    <h5>History</h5> 
    <div id="history_<?php echo $contentBlock->identifier; ?>">
        <table class="content_block_editor_history">
          <thead>
              <tr>
                  <th><?php echo __('Date') ?></th>
                  <th><?php echo __('User') ?></th>
                  <th><?php echo __('Status') ?></th>
                  <th>&nbsp;</th>
              </tr>
          </thead>
          <tbody>
            <?php foreach ($versionHistoryArray as $version): ?>
              <tr>
                <td><?php echo date('d/M/Y H:i', strtotime($version['created_at'])); ?></td>
                <td><?php echo (is_array($version['CreatedBy']) ? $version['CreatedBy']['username'] : '') ?></td>
                <td>
                  <?php if ($version['id'] == $currentContentBlockVersion->id) : ?>
                    <span class="site_sitetree_published">Live version</span>
                  <?php endif; ?>
                  <?php if ($version['id'] == $contentBlockVersion['id']) : ?>
                    <span class="site_sitetree_editing">Currently editing</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($version['id'] != $contentBlockVersion['id']) : ?>
                    <form method="post" action="<?php echo $formTarget; ?>">
                      <input type="hidden" name="load_version_block_id" value="<?php echo $contentBlock->id; ?>" />
                      <input type="hidden" name="load_version_id" value="<?php echo $version['id']; ?>" />
                      <input type="submit" class="btn_load frm_submit" name="load_version" value="Load version" />
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
    </div>
    </div>
  </div>
  <br style="clear:both" />
</div>