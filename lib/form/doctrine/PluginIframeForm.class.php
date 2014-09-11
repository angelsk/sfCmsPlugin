<?php

/**
 * PluginIframe form.
 *
 * @package    site_cms
 * @subpackage form
 * @author     Jo Carter
 * @version    SVN: $Id: sfDoctrineFormPluginTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
abstract class PluginIframeForm extends BaseIframeForm
{
  public function setup()
  {
    parent::setup();

    $this->useFields(array('sitetree_id', 'url', 'file_name', 'layout'));

    $this->widgetSchema['sitetree_id'] = new sfWidgetFormInputHidden();

    // Set layout
    $config  = sfConfig::get('app_site_iframe', array());
    $layouts = (isset($config['layouts']) ? $config['layouts'] : array());

    if (empty($layouts))
    {
      unset($this['layout']);
    }
    else if (1 < count($layouts))
    {
      $this->widgetSchema['layout'] = new sfWidgetFormChoice(array('choices' => $layouts));
    }
    else
    {
      $layouts                      = array_keys($layouts);
      $this->widgetSchema['layout'] = new sfWidgetFormInputHidden();
      $this->setDefault('layout', $layouts[0]); // this is set when a new iframe is created
    }

    // Files
    $location = $config['folder'];
    $files    = array();
    $this->readDir($files, $location);

    ksort($files);

    $combineFiles = array('' => 'No files uploaded');
    if (!empty($files)) $combineFiles = array(''=>'&nbsp;') + array_combine($files, $files);

    $this->widgetSchema['file_name']    = new sfWidgetFormChoice(array('choices' => $combineFiles));
    $this->validatorSchema['file_name'] = new sfValidatorChoice(array('choices' => $files, 'required' => false));

    // URL
    $this->validatorSchema['url'] = new sfValidatorUrl(array('required'=>false));

    // Labels etc
    $this->widgetSchema->setLabel('url', 'URL');
    $this->widgetSchema->setHelp('url', 'Enter a URL for the iFrame content, or select a file below (recommended)');
    $this->widgetSchema->setLabel('file_name', 'HTML file');
    $this->widgetSchema->setHelp('file_name', 'FTP HTML files to the static folder to have them appear in this list for selection');

    // Post validator - url OR filename must be filled in
    $this->validatorSchema->setPostValidator(new sfValidatorCallback(
                          array('callback' => array($this, 'checkLocation')),
                          array('required' => 'Need either a URL or a file', 'invalid' => 'Cannot use both URL and file')));
  }

  /**
   * Get uploaded HTML files
   *
   * @param array $files
   * @param string $location
   * @param string $prefix
   */
  protected function readDir(&$files, $location, $prefix = '')
  {
    $dh = opendir($location);

    while (($file = readdir($dh)) !== false)
    {
      if ('.' != substr($file, 0, 1))
      {
        if (is_file($location . DIRECTORY_SEPARATOR . $file))
        {
          if (false != strstr($file, '.htm')) // checks html and htm files
          {
            $files[] = $prefix . $file;
          }
        }
        else if (is_dir($location . DIRECTORY_SEPARATOR . $file) && 'static' != $file)
        {
          $this->readDir($files, $location . DIRECTORY_SEPARATOR . $file, $file . DIRECTORY_SEPARATOR);
        }
      }
    }

    closedir($dh);
  }

  /**
   * Can only have one of filename and url
   *
   * @param sfValidator $validator
   * @param array $values
   * @throws sfValidatorError
   */
  public function checkLocation($validator, $values)
  {
    if (empty($values['file_name']) && empty($values['url']))
    {
      throw new sfValidatorError($validator, 'required');
    }
    else if (!empty($values['file_name']) && !empty($values['url']))
    {
      throw new sfValidatorError($validator, 'invalid');
    }

    return $values;
  }
}
