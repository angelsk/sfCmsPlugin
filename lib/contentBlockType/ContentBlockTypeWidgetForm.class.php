<?php
class ContentBlockTypeWidgetForm extends ContentBlockTypeForm
{
  /**
   * Set up the field
   *
   * @see ContentBlockType/ContentBlockTypeForm::configure()
   */
  public function configure()
  {
    parent::configure();

    $definition     = $this->getObject()->getContentBlockVersion()->getDefinition();
    $widgetClass    = $definition['widget'];
    $validatorClass = $definition['validator'];
    
    if (!isset($definition['widget_options']))     $definition['widget_options'] = array();
    if (!isset($definition['widget_attributes']))  $definition['widget_attributes'] = array();
    if (!isset($definition['validator_options']))  $definition['validator_options'] = array();
    if (!isset($definition['validator_messages'])) $definition['validator_messages'] = array();
    
    $this->widgetSchema['value']    = new $widgetClass($definition['widget_options'], $definition['widget_attributes']);
    $this->validatorSchema['value'] = new $validatorClass($definition['validator_options'], $definition['validator_messages']);
    $this->widgetSchema->setLabel('value','&nbsp;');
  }

  /**
   * For the interface, though not used here because validator options already processed
   */
  protected function getValidatorOptions()
  {
    
  }
  
  /**
   * Link to the Content block
   */
  public function getModelName()
  {
    return 'ContentBlockTypeWidget';
  }
}