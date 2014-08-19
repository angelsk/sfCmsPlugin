<?php
class changeSitetreeForm extends SitetreeForm 
{
  public function setup()  
  {
    parent::setup();
    
    // set sitetree for form
    $tree = siteManager::getInstance()->getSitetreeForForm($this->object->site, null, false, array('lft'=>$this->object->lft, 'rgt'=>$this->object->rgt), 'id');
    
    $this->widgetSchema['parent'] = new sfWidgetFormChoice(array('choices'=>$tree));
    $this->validatorSchema['parent'] = new sfValidatorCallback(array('callback'=>array($this, 'checkParent')), array('invalid'=>'This is already the parent page'));
    $this->widgetSchema->setHelp('parent', 'Select where you want to move this page to - the page you select will become the parent page');
    
    $this->useFields(array('parent', 'site'));
  }
  
  
  public function checkParent($validator, $value)
  {
    $currentParent = $this->object->getNode()->getParent();
    
    if ($value == $currentParent->route_name)
    {
      $error = new sfValidatorError($validator, 'invalid');
      throw new sfValidatorErrorSchema($validator, array('parent' => $error));
    }
    
    return $value;
  }
  
  
  public function doSave($con = null) 
  {
    $parent = SitetreeTable::getInstance()->findOneById($this->getValue('parent'));
    $this->object->getNode()->moveAsLastChildOf($parent);
  }  }