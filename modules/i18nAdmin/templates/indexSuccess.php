<?php
sfContext::getInstance()->getResponse()->setTitle(htmlentities(__('Translation tool'), null, 'utf-8', false), false);

slot('breadcrumbs', get_partial('sitetree/breadcrumbs', array(
  'breadcrumbs' => array(link_to('Translations', 'i18nAdmin/index'))
)));
?>

<div id="sf_admin_container">

  <h1><?php echo __('Translation tool'); ?></h1>

  <?php if (!empty($errors)) : ?>
    <div class="error">
      <?php foreach ($errors as $error) : 
        $errorId = is_int($error) ? $error : $error['id']; ?>
        <p>
          <?php switch ($errorId) {
            case 10:
              echo __('I18N is not enabled it must be set to true in settings.yml');
              break;
            case 11:
              echo __('The file: %file%, is not writable and cannot be set to be writable', array('%file%'=>$error['--file--']));
              break;
            case 12:
              echo __('There are no translations available for this application');
              break;
            case 13:
              echo __('There are no translations available for this application in this language');
              break;  
            default:
              echo __('There was a problem loading the translations, please contact the administrator');
          } ?>
        </p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  
  <div id="sf_admin_header">
    <div class='sitetreeInfo'>
      <h3><?php echo __('To change the content on the site in the selected language enter your translation in the text area provided.'); ?></h3>
      
      <h3>
        <?php echo image_tag('/sfCmsPlugin/images/help.png', array('title' => __('Help'), 'alt'=>__('Help'))); ?>
        <?php echo __('Please note that multiple sites may share this language, so do not change the spirit of the text when translating'); ?>
      </h3>
    
      <p>
        <?php echo image_tag('/sfCmsPlugin/images/help.png', array('title' => __('Help'), 'alt'=>__('Help'))); ?>
        <?php echo __("If there is a '%1%' (etc) in the translation string, this represents a dynamic variable which is added in to the static string in the code. It is important that the variable is included in the translation in the EXACT format it is in the original string."); ?>
      </p>
      <p>
        <?php echo image_tag('/sfCmsPlugin/images/help.png', array('title' => __('Help'), 'alt'=>__('Help'))); ?>
        <?php echo __("There is a special translation string created to deal with numbers and plurals.  This takes the format: [0]No ITEMS|[1]1 ITEM|(1,+Inf]%1% ITEMS, where %1% represents the number of items.  The code selects the correct phrase dependant on the number.  It is important that the [0], [1] and (1,+Inf] remain EXACTLY the same in the translation and are NOT translated themselves."); ?>
      </p>
    </div>
  </div>
  
  <?php if (!empty($messages)) :
    $start = '';
    $chars = range('a', 'z'); ?>
    <div id="sf_admin_content">
      <h1>
        <?php echo format_number_choice('[0]No strings to translate|[1]1 string to translate|(1,+Inf]%1% strings to translate', array('%1%'=>count($messages)), count($messages)); ?> 
        (<?php echo __('Language'); ?>: <?php echo sfCultureInfo::getInstance()->getLanguage($selected_lang); ?>)
      </h1>
    
      <div class="sf_admin_form">
        <form method="post" action="<?php echo url_for('i18nAdmin/index'); ?>" class="translation" id="translation">
          <ul class="nav" id="nav">
            <li><a href="#symbol">#</a></li>
            <?php foreach ($chars as $char) : ?>
              <li><a href="#<?php echo $char; ?>"><?php echo $char; ?></a></li>
            <?php endforeach; ?>
          </ul>
          <br class="clear" />
        
          <?php if ($sf_user->hasFlash('notice')) : ?>
            <div class="notice"><?php echo $sf_user->getFlash('notice'); ?></div>
          <?php endif; ?>
        
          <h2 id="symbol">#
            <span class="right small">
              <b>Skip to: </b>
              <a href="#buttons"><?php echo __('Save'); ?></a> |
              <a href="#nav"><?php echo __('Nav'); ?></a>
            </span>
          </h2>
          
          <fieldset id="sf_fieldset_none">
        
            <?php foreach ($messages as $id => $msg) :
              $first = strtolower(substr(trim($msg['key']), 0, 1)); ?>
              <?php if ($first != $start && in_array($first, $chars)) : 
                $start = $first; ?>
                </fieldset>
                
                <h2 id="<?php echo $first; ?>"><?php echo $first; ?>
                  <span class="right small">
                    <b>Skip to: </b>
                    <a href="#buttons"><?php echo __('Save'); ?></a> |
                    <a href="#nav"><?php echo __('Nav'); ?></a>
                  </span>
                </h2>
                
                <fieldset id="sf_fieldset_none">
              <?php endif; ?>
              
              <div class="sf_admin_form_row <?php //if ($error) echo 'errors'; ?>">
                <div>
                  <label for="translation_string_<?php echo $msg['id']; ?>" class="long"><?php echo html_entity_encode($msg['key'], ENT_NOQUOTES, 'UTF-8'); ?></label>
                  <div class="content">
                    <textarea name="translation[string_<?php echo $msg['id']; ?>]" id="translation_string_<?php echo $msg['id']; ?>"><?php echo html_entity_decode($msg['value'], ENT_NOQUOTES, 'UTF-8'); ?></textarea>
                  </div>
                </div>
              </div>
              
            <?php endforeach; ?>
          </fieldset>
          
          <?php if ($canPublish) : ?>
            <ul class="sf_admin_actions" id="buttons">
              <li class="sf_admin_action_save"><input type="submit" value="<?php echo __('Save'); ?>"></li>
            </ul>
          <?php else : ?>
            <p id="buttons"><?php echo __('Sorry, you need publish permissions to be able to save/ edit translations'); ?></p>
          <?php endif; ?>
        </form>
      </div>
    </div>
    <br class="clear" /><br />
  <?php endif; ?>
</div>
