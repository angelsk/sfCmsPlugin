<?php

/**
 * ContentBlock type base class, Content blocks should extend this and implement 
 * or override the interface methods
 * 
 * This is a base class which can be used to make ContentBlocks.  It deals with
 * the editing, rendering and copying etc.
 *
 * See the text and HTML ones for reference.
 */
abstract class ContentBlockType implements ContentBlockTypeInterface 
{	
    /**
     * @var ContentBlockVersion
     */
    protected $ContentBlockVersion;
    
	/**
	 * @var string
	 */
	protected $value;
	
	/**
	 * Get value for block type 
	 */
	public function getValue() 
	{
		if (!$this->value) 
		{
			$this->setValue($this->getContentBlockVersion()->getValue());
		}
		
		return $this->value;
	}
	
	/**
	 * Set the value for the block type
	 * 
	 * @param string $value
	 */
	public function setValue($value) 
	{
		$this->value = $value;
	}
    
    /**
     * We need to know the ContentBlockVersion that we're dealing with.
     *
     * @param ContentBlockVersion $ContentBlockVersion
     */
    public function __construct(ContentBlockVersion $ContentBlockVersion) 
	{
        $this->ContentBlockVersion = $ContentBlockVersion;
    }
    
    /**
     * Get the Content block version this is a type for.
     *
     * @return ContentBlockVersion
     */
    public function getContentBlockVersion() 
	{
        return $this->ContentBlockVersion;
    }
    
    /**
     * Get the name of the form field we use to edit
     *
     * @return string
     */
    public function getFormName() 
	{
        return 'Content_block_' . $this->ContentBlockVersion->Content_block_id;
    }
	
    /**
     * Get the parameters for the Content block version.  This is the definition
     * array merged with any extra params which are merged in when it's rendered
     *
     * @return array
     */
	public function getParameters() 
	{
	    return $this->ContentBlockVersion->getDefinition();
	}	
	
    /**
     * Get a particular parameter
     * 
     * This is the definition array merged with any extra params which are merged 
     * in when it's rendered
     *
     * @param string $name Parameter name
     * @param string $default The default Content of the parameter if not set
     * 
     * @return mixed
     */
	protected function getParameter($name, $default = null) 
	{
	    $params = $this->getParameters();
	    
	    return (isset($params[$name]) ? $params[$name] : $default);
	}
	
	/**
     * Get the value from the request object
     * 
	 * @param sfWebRequest $request
	 */
	protected function getValueFromRequest(sfWebRequest $request) 
	{
	    $values = $request->getParameter($this->getFormName());
	    return $values['value'];
	}
	
	/**
	 * Render the value
	 * 
	 * @param string $value
	 * @return string
	 */
	public function renderFromValue($value) 
	{
	    return $value;
	}

	/**
	 * Copy the value of this Content block to the one given.  The one given will always
	 * be of the same type.
	 * 
	 * This should call save()
	 *
	 * @param ContentBlockVersion $toContentBlock
	 */
	public function copyTo(ContentBlockVersion $toContentBlock) 
	{
	    $toContentBlock->value = $this->ContentBlockVersion->value;
	    $toContentBlock->save();
	}
	
	/**
	 * Delete any Content associated with this version.
	 */
	public function delete() 
	{
	}
	
	/**
	 * Free circular references, like Doctrine_Record::free()
	 */
	public function free() 
	{
	    unset($this->ContentBlockVersion);
	}
	
	/**
	 * @see ContentBlockType/ContentBlockTypeInterface::render()
	 * 
	 * @return string
	 */
	public function render() 
	{
	    return $this->renderFromValue($this->ContentBlockVersion->value);
	}
	
	/**
	 * @see ContentBlockType/ContentBlockTypeInterface::renderFromRequest()
	 * 
	 * @param sfWebRequest $request
	 * @return string
	 */
	public function renderFromRequest(sfWebRequest $request) 
	{
	    $valueFromRequest = $this->getValueFromRequest($request);
	    
	    if ($valueFromRequest === null) 
		{
	        return $this->render();
	    } 
	    else 
		{
	        return $this->renderFromValue($valueFromRequest);
	    }
	}
	
	/**
	 * @see ContentBlockType/ContentBlockTypeInterface::editIsChanged()
	 * 
	 * @param sfWebRequest $request
	 * @return string
	 */
	public function editIsChanged(sfWebRequest $request) 
	{
	    $newValue = $this->getValueFromRequest($request);
	    
	    return ($newValue != $this->ContentBlockVersion->value);
	}
	
	/**
	 * @see ContentBlockType/ContentBlockTypeInterface::editDuplicateAndSave()
	 */
	public function editDuplicateAndSave(ContentBlockVersion $newContentBlock, sfWebRequest $request) 
	{
		$newContentBlock->value = $this->getValueFromRequest($request);
  	    $newContentBlock->save();
	}
	
	/**
	 * @see ContentBlockType/ContentBlockTypeInterface::editRenderJavascript()
	 * 
	 * @param sfWebRequest $request
	 * @return string
	 */
	public function editRenderJavascript(sfWebRequest $request) 
	{ 
	}
}