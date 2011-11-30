<?php
class createSitetreeForm extends sitetreeForm 
{
  protected $parent;

  public function __construct($parent) 
  {
    $this->parent   = $parent;
    $sitetree       = new sitetree();
    $sitetree->site = $parent->site;
    
    parent::__construct($sitetree);
  }

  public function setup()  
  {
    parent::setup();

    $this->widgetSchema['route_name']      = new sfWidgetFormInputText();
    $this->validatorSchema['route_name']   = new sfValidatorRegex(array(
                            'pattern' => '/^[a-z0-9_-]+$/i'
                          ));
    $this->getWidgetSchema()->setLabel('route_name', 'Unique page identifier <em>*</em>');
    
    $this->widgetSchema['parent']        = new sfWidgetFormInputHidden();
    $this->validatorSchema['parent']     = new sfValidatorString(array('max_length' => 255, 'required' => false));

    $this->getValidatorSchema()->setPostValidator(
                        new sfValidatorDoctrineUnique(array(
                          'model' => 'sitetree',
                          'column' => array('route_name')
                        ),
                        array('invalid'=>'This identifier already exists for this site, please modify it so that it is unique'))
                      );

    if (!$this->canAdmin) 
    {
      unset($this['is_locked']);
    }
    
    unset($this['is_active']); // Start off with all pages unpublished to avoid 404s and no content
  }

  public function doSave($con = null) 
  {
    $this->updateObject();
    
    $this->object->is_active = false;
    $this->object->getNode()->insertAsLastChildOf($this->parent);
  }  }