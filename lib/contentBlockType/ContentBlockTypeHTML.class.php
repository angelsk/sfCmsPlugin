<?php

/**
 * Basic HTML Content block
 *
 * @see ContentBlockType for details
 */
class ContentBlockTypeHTML extends ContentBlockType 
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
	    $form = new ContentBlockTypeHTMLForm($this);
	    
		if ($request->hasParameter($field) 
	    		&& ($request->hasParameter('save') || $request->hasParameter('save_and_publish') || $request->hasParameter('preview'))) 
		{
		    $form->bind($request->getParameter($field)); 
	    }
	    
	    return $form->render();
	}

	/**
	 * @see ContentBlockType/ContentBlockTypeInterface::editRenderJavascript()
	 * @see ContentBlockTypeHTMLForm::getConfig()
	 * 
	 * @param sfWebRequest $request
	 * @return string
	 */
	public function editRenderJavascript(sfWebRequest $request) 
	{
		$tinyMceFile = sfConfig::get('app_site_tiny_mce_file', '/ContentPlugin/js/tiny_mce/tiny_mce.js');
      	sfContext::getInstance()->getResponse()->addJavascript($tinyMceFile);
      	
      	return '';
	}

	/**
	 * Validation options:
	 * 
	 *     character_limit: 500    # excluding HTML markup
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
	    $form = new ContentBlockTypeHTMLForm($this);
	    
	    if ($request->hasParameter($field)) {
	    	$form->bind($request->getParameter($field));
	    }
	    
	    return $form->isValid();
	}
}