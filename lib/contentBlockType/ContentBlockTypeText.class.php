<?php

/**
 * Basic text Content block
 *
 * @see ContentBlockType for details
 */
class ContentBlockTypeText extends ContentBlockType 
{	
	/**
	 * @see ContentBlockType/ContentBlockTypeInterface::editRender()
	 * 
	 * @param sfWebRequest $request
	 * @return string
	 */
	public function editRender(sfWebRequest $request) 
	{
	    $field = $this->getFormName();
	    $form = new ContentBlockTypeTextForm($this);
	    
		if ($request->hasParameter($field) 
	    		&& ($request->hasParameter('save') || $request->hasParameter('save_and_publish') || $request->hasParameter('preview'))) 
		{
		    $form->bind($request->getParameter($field)); 
	    }
	    
	    return $form->render();
	}

	/**
	 * Validation options:
	 * 
	 *     character_limit: 500    # unencoded
	 *     required: true		   # mandatory Content field
	 * 
	 * @see ContentBlockType/ContentBlockTypeInterface::editIsValid()
	 * 
	 * @param sfWebRequest $request
	 * @return boolean
	 */
	public function editIsValid(sfWebRequest $request) 
	{
		$field = $this->getFormName();
	    $form = new ContentBlockTypeTextForm($this);
	    
	    if ($request->hasParameter($field)) 
		{
	    	$form->bind($request->getParameter($field));
	    }
	    
	    return $form->isValid();
	}

    /**
     * Render from value - escape value
     * 
     * @see ContentBlockType/ContentBlockType::renderFromValue()
     * 
     * @param string $value
     * @return string
     */
	public function renderFromValue($value) 
	{
	   return is_string($value) ? htmlentities($value, ENT_QUOTES, sfConfig::get('sf_charset'), false) : $value;
	}
}