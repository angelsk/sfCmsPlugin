<?php

/**
 * Basic HTML Content block
 *
 * @see ContentBlockType for details
 */
class ContentBlockTypeHTML extends ContentBlockType
{
	public $form;

	/**
	 * @see ContentBlockType/ContentBlockTypeInterface::editRender()
	 *
	 * @param sfWebRequest $request
	 * @return string
	 */
	public function editRender(sfWebRequest $request)
	{
		$field = $this->getFormName();
		$this->form = new ContentBlockTypeHTMLForm($this);

		if ($request->hasParameter($field)
		&& ($request->hasParameter('save') || $request->hasParameter('save_and_publish') || $request->hasParameter('preview')))
		{
			$this->form->bind($request->getParameter($field));
		}

		return $this->form->render();
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
		// add javascript and stylesheets for form
		$response = sfContext::getInstance()->getResponse();

		foreach ($this->form->getJavascripts() as $file)
		{
			$response->addJavascript($file);
		}
		 
	 	$response = sfContext::getInstance()->getResponse();

	 	foreach ($this->form->getStylesheets() as $file => $media)
	 	{
	 		$response->addStylesheet($file, '', array('media' => $media));
	 	}

		return '';
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