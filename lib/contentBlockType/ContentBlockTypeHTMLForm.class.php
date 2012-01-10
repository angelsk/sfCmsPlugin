<?php
class ContentBlockTypeHTMLForm extends ContentBlockTypeForm
{
	/**
	 * Set up the field
	 *
	 * @see ContentBlockType/ContentBlockTypeForm::configure()
	 */
	public function configure()
	{
		parent::configure();
		
		$definition = $this->getObject()->getContentBlockVersion()->getDefinition();
		
		if (isset($definition['with_image']) && true == $definition['with_image'])
		{
		  $tag = (isset($definition['tag']) ? $definition['tag'] : '');
		  $width = (isset($definition['image_width']) ? $definition['image_width'] : '');
		  $height = (isset($definition['image_height']) ? $definition['image_height'] : '');
		  
		  sfImagePoolUtil::addImagePoolMooEditable($this, 'value', array('image_tag'=>$tag, 'image_width'=>$width, 'image_height'=>$height), $this->getConfig());
		}
    else 
    {
		  $this->widgetSchema['value'] = new sfWidgetFormTextareaMooEditable($this->getConfig());
    }
    
		$this->validatorSchema['value'] = new sfEnhancedValidatorString($this->getValidatorOptions(), array('max_length'=>'Character limit of %max_length% characters exceeded. Text was %current_length% characters long (excluding HTML markup)'));
		$this->widgetSchema->setLabel('value','&nbsp;');
	}

	protected function getConfig()
	{
		$options = array();
		$definition = $this->getObject()->getContentBlockVersion()->getDefinition();
		
		if (isset($definition['options']))
		{
			$options = $definition['options'];
		}
		
		return $options;
	}

	/**
	 * Get validation from the config
	 */
	protected function getValidatorOptions()
	{
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
		return 'ContentBlockTypeHTML';
	}
}