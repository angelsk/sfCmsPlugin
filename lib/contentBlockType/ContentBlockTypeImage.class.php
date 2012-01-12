<?php

/**
 * Basic text Content block
 *
 * @see ContentBlockType for details
 */
class ContentBlockTypeImage extends ContentBlockType
{
  private $_images = null;
  private $_nb_images = 0;
  public  $id;
  
  /**
   * @see ContentBlockType/ContentBlockTypeInterface::editRender()
   *
   * @param sfWebRequest $request
   * @return string
   */
  public function editRender(sfWebRequest $request)
  {
    $field = $this->getFormName();
    $form = new ContentBlockTypeImageForm($this);
    
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
   * @see ContentBlockType/ContentBlockTypeInterface::editIsValid()
   *
   * @param sfWebRequest $request
   * @return boolean
   */
  public function editIsValid(sfWebRequest $request)
  {
    $field = $this->getFormName();
    $form = new ContentBlockTypeImageForm($this);

    if ($request->hasParameter($field))
    {
      $form->bind($request->getParameter($field));
    }

    return $form->isValid();
  }
  
  /**
   * @see ContentBlockType/ContentBlockTypeInterface::editDuplicateAndSave()
   */
  public function editDuplicateAndSave(ContentBlockVersion $newContentBlock, sfWebRequest $request)
  {
    $value = $this->getValueFromRequest($request);
    
    // remove empty image pool thingy
    foreach ($value as $idx => $raw)
    {
      if (empty($raw)) unset($value[$idx]);
    }
    
    if (is_array($value)) $value = serialize($value);
    
    $newContentBlock->value = $value;
    $newContentBlock->save();
  }
  
  /**
   * @see ContentBlockType/ContentBlockTypeInterface::editIsChanged()
   */
  public function editIsChanged(sfWebRequest $request)
  {
    $newValue = $this->getValueFromRequest($request);
    
    // remove empty image pool thingy
    foreach ($newValue as $idx => $raw)
    {
      if (empty($raw)) unset($newValue[$idx]);
    }
    
    if (is_array($newValue)) $newValue = serialize($newValue);
    
    return ($newValue != $this->ContentBlockVersion->value);
  }
  
  /**
   * @see ContentBlockType/ContentBlockTypeInterface::getValue()
   */
  public function getValue()
  {
    $value = parent::getValue();
    
    // unserialize if from DB
    if (!is_array($value))
    {
      $value = unserialize($value);
    }
    
    return $value;
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
    // unserialize if from DB
    if (!is_array($value))
    {
      $value = unserialize($value);
    }
    
    // remove empty image pool thingy if from request
    foreach ($value as $idx => $raw)
    {
      if (empty($raw)) unset($value[$idx]);
    }
    
    $images = $this->getPoolImages(null, null, $value);
    
    // if multiple return array of images
    if ($this->allowSelectMultiple())
    {
      return $images;
    }
    // else return image
    else 
    {
      return (0 < $images->count() ? $images->getFeaturedImage() : null);
    }
  }
  
  /**
   * Because it's not image poolable - we create the following methods for the image chooser
   */
  public function getId()
  {
    return $this->getContentBlockVersion()->id;
  }
  
  public function getTagRestriction()
  {
    $definition = $this->getContentBlockVersion()->getDefinition();
    
    $tag = (isset($definition['tag']) ? $definition['tag'] : false);
    if (!is_array($tag) && false !== $tag) $tag = array($tag);
    
    return $tag;
  }
  
  public function allowSelectMultiple()
  {
    $definition = $this->getContentBlockVersion()->getDefinition();
    
    $multiple = (isset($definition['multiple']) ? $definition['multiple'] : false);
    
    return $multiple;
  }
  
 /**
   * Get image pool images for a version
   * 
   * @param Doctrine_Record $object
   * @param Doctrine_Query $query
   * @param $value
   * @return sfImagePoolImageCollection
   */
  public function getPoolImages(Doctrine_Record $object = null, Doctrine_Query $query = null, $value = null)
  {    
    if (is_null($this->_images)) $this->_images = new sfImagePoolImageCollection('sfImagePoolImage');
    
    $image_ids = (!is_null($value) ? $value : $this->getValue());

    // If we don't have images - don't want to return all images
    if (!empty($image_ids)) 
    {
      if (is_null($query)) $query = sfImagePoolImageTable::getInstance()->createQuery('i');
      
      $query->andWhereIn('i.id',$image_ids);

      $images = $query->execute();

      $this->_images->merge($images);
      $this->_images->takeSnapshot();
    }

    return $this->_images;
  }
}