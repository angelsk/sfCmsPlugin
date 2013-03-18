<?php

/**
 * Dynamic widget Content block- specify widget options and validation on the fly in templates
 * Would advise for use with simple widgets only - a dropdown / checkbox - see README
 *
 * @see ContentBlockType for details
 */
class ContentBlockTypeWidget extends ContentBlockType
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
    $form = new ContentBlockTypeWidgetForm($this);

    if ($request->hasParameter($field)
      && ($request->hasParameter('save') || $request->hasParameter('save_and_publish') || $request->hasParameter('preview')))
    {
      $form->bind($request->getParameter($field));
    }

    return $form;
  }
  
  /**
   * @see ContentBlockType/ContentBlockTypeInterface::editDuplicateAndSave()
   */
  public function editDuplicateAndSave(ContentBlockVersion $newContentBlock, sfWebRequest $request)
  {
    $field = $this->getFormName();
    $form = new ContentBlockTypeWidgetForm($this);
    $form->bind($request->getParameter($field));
    
    $newContentBlock->value = $form->getValue('value'); // Get value returned from validator
    $newContentBlock->save();
  }

  /**
   * @see ContentBlockType/ContentBlockTypeInterface::editIsValid()
   *
   * @param sfWebRequest $request
   * @return boolean
   */
  public function editIsValid(sfWebRequest $request)
  {
    $field = $this->getFormName();
    $form = new ContentBlockTypeWidgetForm($this);

    if ($request->hasParameter($field))
    {
      $form->bind($request->getParameter($field));
    }

    return $form->isValid();
  }

  /**
   * Render from value
   *
   * @see ContentBlockType/ContentBlockType::renderFromValue()
   *
   * @param string $value
   * @return string
   */
  public function renderFromValue($value)
  {
    return $value;
  }
}