<?php
$formId = 'ContentBlockEditor' . $contentGroup->id;
$lang = $contentGroup->getCurrentLang();
$formTarget = ($sf_data->offsetExists('formTarget') ? $sf_data->getRaw('formTarget') : '');
?>

<br />

<?php if ($sf_user->hasFlash('content_error')): ?>
  <div class="ui-widget">
  <div class="ui-state-error ui-corner-all" style="margin: 10px; padding: 7px 0px 0px 7px;"> 
    <p><span class="ui-icon ui-icon-alert left"></span> 
    <?php echo $sf_user->getFlash('content_error'); ?></p>
  </div>
  </div>
<?php endif; ?>

<?php if ($sf_user->hasFlash('content_notice')): ?>
  <div class="ui-widget">
   <div class="ui-state-highlight ui-corner-all" style="margin: 10px; padding: 7px 0px 0px 7px;"> 
    <p><span class="ui-icon ui-icon-info left"></span>
    <?php echo $sf_user->getFlash('content_notice'); ?></p>
   </div>
  </div>
<?php endif; ?>

<div class="content_block_editor">
  <form id="<?php echo $formId ?>" method="post" action="<?php echo $formTarget; ?>" enctype="multipart/form-data">
    <fieldset class="sitetreeInfo">
        <span style="float:left;">Load versions:</span>
          <input type="submit" name="live_versions" value="Currently live" class="btn_load frm_submit" />
          <input type="submit" name="new_versions" value="Newest" class="btn_load frm_submit" />
    </fieldset>
    
      <fieldset>
          <input type="hidden" name="content_group_id" value="<?php echo $contentGroup->id?>" />
          <input type="hidden" name="sf_culture" value="<?php echo $sf_user->getCulture(); ?>" />
          <input type="hidden" name="lang" value="<?php echo esc_entities($lang) ?>" />
          
          <div class='content_block_editor_items'>
              <?php foreach ($contentBlocks as $contentBlock): ?>
                  <?php $contentBlockVersion = $contentBlockVersions[$contentBlock->identifier]; ?>
  
                  <?php include_partial('contentAdmin/editorItem', array('contentGroup' => $contentGroup, 'contentBlock' => $contentBlock, 'contentBlockVersion' => $contentBlockVersion, 'formTarget' => $formTarget)); ?>
              <?php endforeach; ?>
          </div>
      </fieldset>
  
      <div style="display: none;" class="content_block_editor_loading"><img src="/sfCmsPlugin/images/ajax-bar.gif" alt="Loading..." /></div>
  
      <fieldset class="sitetreeInfo">
          <input type="submit" name="preview" id="<?php echo $formId ?>Preview" value="Preview" class="btn_save float_r frm_submit" />
          <input type="submit" name="save" value="Save version" class="btn_save float_r frm_submit" />
          <input type="submit" name="save_and_publish" value="Save and publish" class="btn_save float_r frm_submit"/>
        
        <?php if (isset($cancelUrl)) echo button_to('Cancel', $cancelUrl, array('class'=>'btn_cancel float_r frm_submit')); ?>
        <br class="clear" />
    </fieldset>
      <br/>
  
      <iframe style="width: 95%; height: 500px;" name="<?php echo $formId ?>Iframe" id="<?php echo $formId ?>Iframe"></iframe>
  </form>
</div>

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

<?php
$javascript = "";
foreach ($contentBlockVersions as $contentBlockVersion) 
{
  $versionJs =  $contentBlockVersion->getContentBlockType()->editRenderJavascript($sf_request->getRawValue());
    if (!empty($versionJs)) 
  {
      $javascript .= "\n\n//Inititialisation javascript for Content block version '" . esc_entities($contentBlockVersion->id) . "'\n" . $versionJs;
    }
}
?>

<?php if (!empty($javascript)) : ?>
  <script type="text/javascript">
  //<![CDATA[
    $(document).addEvent('domready', function() 
    {
      <?php $javascript; ?>
    });
  });
  </script>
<?php endif; ?>
