<?php

/**
 * Sitetree form.
 *
 * @package    sfCmsPlugin
 * @subpackage form
 * @author     Jo Carter <work@jocarter.co.uk>
 * @version    SVN: $Id: sfDoctrineFormTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
abstract class PluginSitetreeForm extends BaseSitetreeForm 
{
  protected $canAdmin;

  public function __construct($sitetree) 
  {
    $this->canAdmin = sfContext::getInstance()->getUser()->isSuperAdmin();

    parent::__construct($sitetree);
  }
  
  public function setup() 
  {
    parent::setup();
    
    $culture = sfContext::getInstance()->getUser()->getCulture();
    $this->embedI18n(array($culture));
    $this->widgetSchema->setLabel($culture, '&nbsp;');
    
    foreach ($this->getStandardWidgets() as $name => $widget)
    {
      $this->widgetSchema[$name] = $widget;
    }

    foreach ($this->getStandardValidators() as $name => $val)
    {
      $this->validatorSchema[$name] = $val;
    }
    
    if (!$this->object->is_locked) 
    {
      $this->widgetSchema->setLabel('base_url', 'Url <em>*</em>');
      $this->widgetSchema->setLabel('target_module', 'Module <em>*</em>');
      $this->widgetSchema->setLabel('prepend_parent_url', 'Prepend parent URL');
      $this->widgetSchema->setLabel('is_core_navigation', 'Core navigation');
      $this->widgetSchema->setLabel('is_locked', 'Lock page');
    }
    
    $this->useFields(array($culture) + array_keys($this->getStandardWidgets()));
    
    $this->widgetSchema->setLabel('is_active', 'Live');
    $this->widgetSchema->setLabel('is_hidden', 'Hide from sitemap');
    
    $this->widgetSchema->setHelp('prepend_parent_url', 'should we prepend the parent\'s url to this one? e.g: /footer/terms or /terms (second is not prepended)');
    $this->widgetSchema->setHelp('route_name', 'This is for development purposes to uniquely identify the page');
    $this->widgetSchema->setHelp('is_hidden', 'The page will be created, but only accessible via a direct URL - useful for competitions for example');
    $this->widgetSchema->setHelp('is_locked', 'Prevent page from being deleted (whilst locked), or the important settings from being changed');
  }
    
  /**
   * Get the available module names as an array in the form:
   *
   * @return array
   */
  public function getModuleNames() 
  {
    $moduleDefinitions = siteManager::getInstance()->getModuleDefinitions();
    $availableModules = siteManager::getInstance()->getAvailableModules();
    
    // get the names of all of the allowed modules
    $moduleNames = array('' => '&nbsp;');
    
    foreach ($availableModules as $module)
    {
      if (isset($moduleDefinitions[$module])) 
      {
        $moduleNames[$module] = $moduleDefinitions[$module]['name'];
      }
    }
    
    // order nicely
    asort($moduleNames);
    
    return $moduleNames;
  }
  
  public function getStandardWidgets() 
  {
    $modules = $this->getModuleNames();

    $fields = array(
      'id'                    => new sfWidgetFormInputHidden(),
      'site'                  => new sfWidgetFormInputHidden(),
      'base_url'              => new sfWidgetFormInput(),
      'prepend_parent_url'    => new sfWidgetFormInputCheckbox(),
      'target_module'         => new sfWidgetFormSelect(array('choices' => $modules)),
      'is_active'             => new sfWidgetFormInputCheckbox(),
      'is_hidden'             => new sfWidgetFormInputCheckbox(),
      'is_core_navigation'    => new sfWidgetFormInputCheckbox(),
    );
    
    if ($this->canAdmin) 
    {
      $fields['is_locked']      = new sfWidgetFormInputCheckbox();
    }
    
    return $fields;
  }
  
  public function getStandardValidators() 
  {
    $modules = $this->getModuleNames();
    
    $fields = array(
      'id'                  => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'base_url'            => new sfValidatorAnd(array(
                                    new sfValidatorCallback(array('callback' => array($this, 'baseRouteValidatorCallback'))),
                                    new sfValidatorRegex(array(
                                        'pattern' => '/^[a-z0-9-_]+$/i',
                                        'required' => !$this->getObject()->getNode()->isRoot()
                                    ),
                                    array('invalid' => 'Invalid - can only contain a-z, 0-9, _ and -'))
                                 ), array('required' => !$this->getObject()->getNode()->isRoot())),
      'target_module'       => new sfValidatorChoice(array('choices' => array_keys($modules))),
      'is_active'           => new sfValidatorBoolean(array('empty_value' => false)),
      'is_hidden'           => new sfValidatorBoolean(array('empty_value' => false)),
      'is_core_navigation'  => new sfValidatorBoolean(array('empty_value' => false)),
      'prepend_parent_url'  => new sfValidatorBoolean(array('empty_value' => false)),
    );
    
    if ($this->canAdmin) 
    {
      $fields['is_locked']   = new sfValidatorBoolean(array('empty_value' => false));
    }
    
    return $fields;
  }
    
  public function baseRouteValidatorCallback($validator, $value) 
  {
    return strtolower(trim($value, '/'));
  }
}
