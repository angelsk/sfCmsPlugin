<?php

/**
 * Basic textarea Content block
 *
 * @see ContentBlockType for details
 */
class ContentBlockTypeTextarea extends ContentBlockType
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
    $form = new ContentBlockTypeTextareaForm($this);

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
   *     character_limit: 500    # unencoded
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
    $form = new ContentBlockTypeTextareaForm($this);

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
    if (!function_exists('simple_format_text')) 
    {
      sfApplicationConfiguration::getActive()->loadHelpers(array('Text'));
    }
    
    return simple_format_text($value);
  }
}