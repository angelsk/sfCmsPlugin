<?php
class ContentBlockTypeImageForm extends ContentBlockTypeForm
{
  /**
   * Set up the field
   *
   * @see ContentBlockType/ContentBlockTypeForm::configure()
   */
  public function configure()
  {
    parent::configure();
    
    $this->widgetSchema['value'] = new sfWidgetFormImagePoolChooser($this->getConfig());
    $this->validatorSchema['value'] = new sfValidatorPass();
    $this->widgetSchema->setLabel('value','&nbsp;');
  }

  protected function getConfig()
  {
    $options = array('object'=>$this->object);
    
    return $options;
  }
  
  protected function getValidatorOptions()
  {
    return array();
  }

  /**
   * Link to the Content block
   */
  public function getModelName()
  {
    return 'ContentBlockTypeImage';
  }
}