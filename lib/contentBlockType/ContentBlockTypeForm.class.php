<?php

/**
 * Base form for Content block type
 * Use alongside model
 * 
 * @author Jo
 */
abstract class ContentBlockTypeForm extends sfFormObject 
{
	/**
	 * As per sfFormDoctrine - set the object 
	 */
	public function __construct($object = null, $options = array(), $CSRFSecret = null) 
	{
    	$class = $this->getModelName();
    	
    	if (!$object) 
		{
      		$this->object = new $class();
    	}
    	else 
		{
      		if (!$object instanceof $class) 
			{
        		throw new sfException(sprintf('The "%s" form only accepts a "%s" object.', get_class($this), $class));
      		}

      		$this->object = $object;
      		$this->isNew = !$this->getObject()->getContentBlockVersion()->exists();
    	}

    	parent::__construct(array(), $options, $CSRFSecret);

    	$this->updateDefaultsFromObject();
  	}
	
  	/**
  	 * Set up the fields
  	 */
	public function configure() 
	{
		$this->widgetSchema['version_id'] = new sfWidgetFormInputHidden();
		$this->validatorSchema['version_id'] = new sfValidatorInteger(array('required'=>true));		
		
		$field = $this->getObject()->getFormName();
		$this->widgetSchema->setNameFormat("{$field}[%s]");
		
		$this->disableCSRFProtection();
	}
	
	/**
	 * Get validation from the config
	 */
	abstract protected function getValidatorOptions();
	
	/**
	 * Link to the Content block 
	 */
	public function getModelName() 
	{
    	return 'ContentBlockType';
  	}
  	
  	/**
  	 * Set the value for the form
  	 */
  	public function updateDefaultsFromObject() 
	{
  		$this->setDefault('value', $this->getObject()->getContentBlockVersion()->value);
  		$this->setDefault('version_id', $this->getObject()->getContentBlockVersion()->id);
  	}
  	
  	/**
	 * Abstract functions
  	 */
  	public function doUpdateObject($values) 
	{
	}
  	
  	public function getConnection() 
	{
	}
  	
  	public function processValues($values) 
	{
	}
}