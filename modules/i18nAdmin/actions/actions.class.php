<?php
/**
 * Manage i18n strings from the backend for the frontend
 *
 * @package sfCmsPlugin
 * @author Jo Carter <jocarter@holler.co.uk>
 */
class i18nAdminActions extends sfActions
{
  // I18N is not enabled it must be set on, in settings.yml
  const ERR_NO_I18N            = 10;
  // Dictionary file is not writable and its not allowed to change permissions
  const ERR_FILE_PERMISSION    = 11;
  // There is no dictionary in this module
  const ERR_NO_DICTIONARIES    = 12;
  // Selected dictionary does not exist (for this lang)
  const ERR_NO_DICT_NOT_EXISTS = 13;

  public function preExecute()
  {
    // Permissions
    $user                     = sfContext::getInstance()->getUser();
    $this->canAdmin           = $user->hasCredential('site.admin');
    $this->canPublish         = ($this->canAdmin || $user->hasCredential('site.publish'));
                              
    // i18n                   
    $this->selected_app       = sfConfig::get('app_site_managed_app', 'frontend');
    $this->selected_lang      = sfContext::getInstance()->getUser()->getCulture();
    $this->selected_catalogue = 'messages';
    $app_dir                  = sfConfig::get('sf_apps_dir') . '/' . $this->selected_app;
    $i18n_dir                 = $app_dir . '/i18n';

    // Variable set up
    $this->errors        = array();
    $this->languages     = array();
    $this->messageSource = null;
    $this->messages      = array();
    
    if (!sfConfig::get('sf_i18n'))
    {
      $this->errors[] = self::ERR_NO_I18N;
    }
    else
    {
      // load messageSource
      $i18n                = sfContext::getInstance()->getI18N();
      $this->messageSource = $i18n->createMessageSource($i18n_dir);
      $catalogues          = $this->messageSource->catalogues();
      
      if (count($catalogues) == 0)
      {
        $this->errors[]      = self::ERR_NO_DICTIONARIES;
        $this->messageSource = null;
      }
      else 
      {
        foreach ($catalogues as $item) // e.g: item = array(0 => 'messages', 1 => 'en');
        {
          $catalogue = $item[0];
          $lang      = $item[1];
          
          if (!array_key_exists($lang, $this->languages))
          {
            $this->languages[$lang] = $lang;
          }
    
          // check permissions for each file
          $this_i18n_file = $i18n_dir.DIRECTORY_SEPARATOR.$lang.DIRECTORY_SEPARATOR.$catalogue.'.xml';
    
          if (!file_exists($this_i18n_file))
          {
            throw new sfException("File does not exist: " . $this_i18n_file);
          }
          else if (!is_writable($this_i18n_file))
          {
            if (!chmod($this_i18n_file, 0777))
            {
              $this->errors[]      = array('id' => self::ERR_FILE_PERMISSION, '--file--' => $this_i18n_file);
              $this->messageSource = null;
            }
          }
        }
        
        if (empty($this->errors))
        {
          if (!in_array($this->selected_lang, $this->languages))
          {
            $this->errors[]      = self::ERR_NO_DICT_NOT_EXISTS;
            $this->messageSource = null;
          }
        }
      }
    }
  }

  /**
   * Executes index action
   * It manages everything with messages
   * (showing, editing, deleting, adding)
   */
  public function executeIndex(sfWebRequest $request)
  {
    if (is_null($this->messageSource))
    {
      $this->setVar('errors', $this->errors, true);
      return sfView::SUCCESS;
    }

    $this->messageSource->setCulture($this->selected_lang);
    $this->messageSource->load($this->selected_catalogue);
    
    $translations = $this->messageSource->read();
    $filename     = $this->selected_lang.DIRECTORY_SEPARATOR.$this->selected_catalogue.'.xml';
    $mt           = array();

    if (count($translations) > 0 && (array_key_exists($filename, $translations)))
    {
      $messages = $translations[$filename];

      foreach ($messages as $key => $value)
      {
        $first = strtolower(substr(trim($key), 0, 1));
        
        $this->messages[$first.$value[1]] = array(
                                        'id'    => $value[1],
                                        'key'   => $key,
                                        'value' => $value[0]
                                     );
                                     
        $mt[$value[1]] = array('key'=>$key, 'value'=>$value[0]);
      }
      
      ksort($this->messages);
      
      if (($request->isMethod(sfWebRequest::POST) || $request->isMethod(sfWebRequest::PUT)) && $request->hasParameter('translation'))
      {
        $counter = 0;
        
        foreach ($request->getParameter('translation') as $id => $translation)
        {
          $id    = str_replace('string_','',$id);
          $tran  = $mt[$id];
          $key   = $tran['key'];
          $value = $tran['value'];
          $first = strtolower(substr(trim($key), 0, 1));
          
          if ($value == $translation) continue;
          
          // Update visible messages
          $this->messages[$first.$id] = array(
                                        'id'    => $id,
                                        'key'   => $key,
                                        'value' => $translation
                                     );
          
          $this->messageSource->update($key, htmlspecialchars($translation, null, 'utf-8', false), null, $this->selected_catalogue);
          $counter++;
        }
        
        if (1 == $counter) $this->getUser()->setFlash('notice', sprintf('%s translation string was updated', $counter));
        else $this->getUser()->setFlash('notice', sprintf('%s translation strings were updated', $counter));
      }
    }
    else
    {
      $this->errors[] = self::ERR_NO_DICT_NOT_EXISTS;
    }
    
    $this->setVar('errors',   $this->errors,   true);
    $this->setVar('messages', $this->messages, true);
  }
}
