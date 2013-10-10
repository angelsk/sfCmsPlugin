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
    
    if (is_null($value)) $value = array();
    
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
    
    if (is_null($newValue)) $newValue = array();
    
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
    if (!is_array($value) && !is_null($value))
    {
      $value = unserialize($value);
    }
    
    // remove empty image pool thingy if from request
    if (!empty($value))
    {
      foreach ($value as $idx => $raw)
      {
        if (empty($raw)) unset($value[$idx]);
      }
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
   * Method for setting images but from ids only.
   * 
   * @param $object
   * @param array $image_ids
   */
  public function setImageIds($image_ids = array(), $object = null)
  {
    $object = is_null($object) ? $this : $object;
    
    if (!is_array($image_ids)) $image_ids = unserialize($image_ids);
    
    
    if (empty($image_ids))
    {
      return $object->getPoolImages()->clear();
    }
    
    $images = sfImagePoolImageTable::getInstance()->getByIds($image_ids);
    $images = $this->matchOrder($images, $image_ids);
    
    $this->setImages($images, $object);
  }
  
  /**
   * When multiple images have been associated with an object via the image chooser widget,
   * the user may well have chosen a specific order. When pulling the associated images back
   * from the DB, Doctrine returns in primary key order, which is incorrect. This method
   * is a dirty way of matching the order of a collection to an order of image ids, which means
   * images are returned in the same order they were associated in.
   *
   * @param $images Doctrine_Collection of images
   * @param $image_ids Array of image ids in a specific order
   *
   * @return Doctrine_Collection
   */
  public function matchOrder(Doctrine_Collection $images, $image_ids)
  {
    if (!is_array($image_ids) || empty($image_ids)) return $images;
    
    $ordered = new Doctrine_Collection('sfImagePoolImage', 'id');

    foreach ($image_ids as $index => $id)
    {
      foreach ($images as $i)
      {
        if ($i['id'] == $id) $ordered->set($i['id'], $i);
      }
    }

    return $ordered;
  }
  
  /**
   * Set images to an object
   * 
   * @param array $images
   * @param Doctrine_Record $object
   */
  public function setImages($images = array(), $object = null)
  {
    $object = is_null($object) ? $this : $object;

    $object->getPoolImages()->clear();
    
    foreach ($images as $image)
    {
      $object->getPoolImages()->add($image);
      
      if (!$object->allowSelectMultiple()) 
      {
        // Stop after adding one if object isn't allowed multiple images
        break;
      }
    }
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
