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
	  
	  // add javascript and stylesheets for form
    $response = sfContext::getInstance()->getResponse();

    foreach ($form->getJavascripts() as $file)
    {
      $response->addJavascript($file);
    }
     
    foreach ($form->getStylesheets() as $file => $media)
    {
      $response->addStylesheet($file, '', array('media' => $media));
    }
	  
		if ($request->hasParameter($field)
		&& ($request->hasParameter('save') || $request->hasParameter('save_and_publish') || $request->hasParameter('preview')))
		{
			$form->bind($request->getParameter($field));
		}

		return $form;
	}

	/**
	 * Validation options:
	 *
	 *     character_limit: 500    # excluding HTML markup
	 *     required: true       # mandatory Content field
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