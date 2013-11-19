<?php

/**
 * PluginRedirect form.
 *
 * @package    site_cms
 * @subpackage form
 * @author     Jo Carter
 * @version    SVN: $Id: sfDoctrineFormPluginTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
abstract class PluginRedirectForm extends BaseRedirectForm
{
  public function setup() 
  {
    parent::setup();

    $this->useFields(array('sitetree_id', 'url', 'status_code'));
    
    $this->widgetSchema['sitetree_id'] = new sfWidgetFormInputHidden();
    
    $this->widgetSchema->setHelp('status_code', 'Set to 301 if this is always going to redirect to this URL; set to 302 if it\'s just temporary');
    $this->widgetSchema->setLabel('status_code', 'HTTP status code');
    $this->widgetSchema->setHelp('url', 'The full URL to redirect to, including any tracking');
    $this->widgetSchema->setLabel('url', 'URL');
  }
}
