<?php
class editSitetreeForm extends SitetreeForm 
{
  public function setup()  
  {
    parent::setup();

    if ($this->object->is_locked) 
    {
      $this->widgetSchema['target_module'] = new sfWidgetFormInputHidden();
  
      unset($this['is_active'], $this['prepend_parent_url'], $this['base_url'], $this['is_hidden'], $this['is_core_navigation']);
    }

    if (!$this->canAdmin) 
    {
      unset($this['is_locked']);
    }
    
    if ($this->object->getNode()->isRoot()) 
    {
      $this->widgetSchema['base_url'] = new sfWidgetFormInputHidden();
      $this->widgetSchema['prepend_parent_url'] = new sfWidgetFormInputHidden();
    }
  }
}