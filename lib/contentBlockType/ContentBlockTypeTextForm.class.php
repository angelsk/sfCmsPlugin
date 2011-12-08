<?php
class ContentBlockTypeTextForm extends ContentBlockTypeForm 
{
	/**
	 * Set up the field
	 * 
	 * @see ContentBlockType/ContentBlockTypeForm::configure()
	 */
	public function configure() 
	{
		parent::configure(); 
		
		$this->widgetSchema['value'] = new sfWidgetFormInputText();
		$this->validatorSchema['value'] = new enhancedValidatorString($this->getValidatorOptions(), array('max_length'=>'Character limit of %max_length% characters exceeded. Text was %current_length% characters long'));
		$this->widgetSchema->setLabel('value','&nbsp;');
	}
	
	/**
	 * Get validation from the config
	 */
	protected function getValidatorOptions() 
	{
		// Use validator if set
	    $definition = $this->getObject()->getContentBlockVersion()->getDefinition();
	    $validatorOptions = array();
	    
	    if (isset($definition['character_limit'])) 
		{
	    	$validatorOptions['max_length'] = (int)$definition['character_limit'];
	    }
	    
	    if (isset($definition['required'])) 
		{
      		if (true == $definition['required']) 
			{
        		$validatorOptions['required'] = true;
      		}
      		else $validatorOptions['required'] = false;
    	}
    	else $validatorOptions['required'] = false;
    
	  	return $validatorOptions;
	}
	
	/**
	 * Link to the Content block 
	 */
	public function getModelName() 
	{
    	return 'ContentBlockTypeText';
  	}
}