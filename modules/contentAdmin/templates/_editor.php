<?php
$formId = 'ContentBlockEditor' . $contentGroup->id;
$lang = $contentGroup->getCurrentLang();
$formTarget = ($sf_data->offsetExists('formTarget') ? $sf_data->getRaw('formTarget') : '');
?>

<?php if ($sf_user->hasFlash('content_error')): ?>
  <div class="error"><?php echo $sf_user->getFlash('content_error'); ?></div>
<?php endif; ?>

<?php if ($sf_user->hasFlash('content_notice')): ?>
  <div class="notice"><?php echo $sf_user->getFlash('content_notice'); ?></div>
<?php endif; ?>

<form id="<?php echo $formId ?>" method="post" action="<?php echo $formTarget; ?>" enctype="multipart/form-data">
  <fieldset class="sitetreeInfo">
    <span style="float:left;">Content actions: &nbsp;</span>
    <input type="submit" name="live_versions" value="Load live content" class="btn_load frm_submit" />
    <input type="submit" name="new_versions" value="Load newest content" class="btn_load frm_submit" />
    <input type="submit" name="clear_cache" value="Clear page cache" class="btn_load frm_submit" />
  </fieldset>
  
  <fieldset>
    <input type="hidden" name="content_group_id" value="<?php echo $contentGroup->id; ?>" />
    <input type="hidden" name="sf_culture" value="<?php echo $sf_user->getCulture(); ?>" />
    <input type="hidden" name="lang" value="<?php echo esc_entities($lang); ?>" />
    
    <div class='content_block_editor_items'>
      <?php foreach ($contentBlocks as $contentBlock): ?>
        <?php $contentBlockVersion = $contentBlockVersions[$contentBlock->identifier]; ?>

        <?php include_partial('contentAdmin/editorItem', array('contentGroup' => $contentGroup, 'contentBlock' => $contentBlock, 'contentBlockVersion' => $contentBlockVersion, 'formTarget' => $formTarget)); ?>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <div style="display: none;" class="content_block_editor_loading"><img src="/sfCmsPlugin/images/ajax-bar.gif" alt="Loading..." /></div>

  <ul class="sf_admin_actions">
    <li class="sf_admin_action_preview"><input type="submit" name="preview" id="<?php echo $formId ?>Preview" value="Preview" /></li>
    <li class="sf_admin_action_save"><input type="submit" name="save" value="Save version" /></li>
    <li class="sf_admin_action_save_and_add"><input type="submit" name="save_and_publish" value="Save and publish" /></li>
    <?php if (isset($cancelUrl)) echo '<li class="sf_admin_action_list">' . link_to('Back', $cancelUrl) . '</li>'; ?>
  </ul>

  <iframe style="margin-left: -25%; width: 150%; height: 600px; margin-top: 20px; border: none;" name="<?php echo $formId ?>Iframe" id="<?php echo $formId ?>Iframe"></iframe>
</form>

<script type="text/javascript">
  $(document).addEvent('domready', function () 
  {
    // hide the iframe
    $('<?php echo $formId ?>Iframe').hide(); 
    
    $('<?php echo $formId ?>Preview').addEvent('click', function(event) 
    {
      // stop form submit
      event.stopPropagation();

      // show the iframe
      $('<?php echo $formId ?>Iframe').show();
      var form = $('<?php echo $formId ?>');
      
      // post our form to the iframe so it can render the Content blocks
      form.set('action', '<?php echo $previewUrl ?>');
      form.set('target', '<?php echo $formId ?>Iframe');
      form.submit();

      // reset so can submit save / publish
      form.set('action', '<?php echo $formTarget; ?>');
      form.set('target', '');
      return false;
    });
  });
</script>
