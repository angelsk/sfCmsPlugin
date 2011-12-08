<?php

/**
 * ListingItem form.
 *
 * @package    site_cms
 * @subpackage form
 * @author     Jo Carter
 * @version    SVN: $Id: sfDoctrineFormTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
abstract class PluginListingItemForm extends BaseListingItemForm 
{
  	public function setup() 
	{
  		parent::setup();
  		
  		$listing = $this->getObject()->getListing();
  		$manager = listingManager::getInstance();
  		
        $this->widgetSchema['title'] = new sfWidgetFormInput();
        $this->validatorSchema['title'] = new sfValidatorString(array('max_length' => 255, 'required'=>true));

		$this->widgetSchema['listing_id'] = new sfWidgetFormInputHidden();
		
		$this->widgetSchema['item_date'] = new sfWidgetFormJQueryDate(array('image'=>'/sitePlugin/images/calendar.png'), array('style'=>'width:auto;'));
		$this->validatorSchema['item_date'] = new sfValidatorDate(array('required'=>true));
		
		$defn = $manager->getTypeDefinition($listing->type); 
		
		if (!isset($defn['use_categories']) || true === $defn['use_categories']) 
		{
			$query = ListingCategoryTable::getInstance()->createQuery('c')->where('c.listing_id = ? AND c.is_active = ?', array($listing->id, true))->innerJoin('c.Translation t')->orderBy('t.title');
			$this->widgetSchema['listing_category_id'] = new sfWidgetFormDoctrineChoice(array('model' => $this->getRelatedModelName('ListingCategory'), 'add_empty' => true, 'query'=>$query));
		
			$this->validatorSchema['listing_category_id'] = new sfValidatorDoctrineChoice(array('model' => $this->getRelatedModelName('ListingCategory'), 'required' => true));
			$this->widgetSchema->setLabel('listing_category_id', 'Category <em>*</em>');
		}
		else 
		{
			$this->widgetSchema['listing_category_id'] = new sfWidgetFormInputHidden();
		}
		
		$this->useFields(array('title', 'slug', 'listing_category_id', 'item_date', 'status', 'is_hidden', 'listing_id'));
		
		$choices = $manager->getItemStatusList($listing->type);
		
		if (!empty($choices)) 
		{
			$statuses = array('' => '&nbsp;') + $choices;
			$this->widgetSchema['status'] = new sfWidgetFormChoice(array('choices'=>$statuses));
		}
		else unset($this['status']);
		
		$this->widgetSchema->setLabel('slug', 'Item page url <em>*</em>');
		$this->widgetSchema->setLabel('title', 'Title <em>*</em> (T)');
		$this->widgetSchema->setLabel('item_date', 'Article date <em>*</em>');
		$this->widgetSchema->setLabel('is_hidden', 'Hide item from listing');
		
        if (!$this->getObject()->exists()) 
		{
            // title is sluggable, this is automatic on creation
            unset($this['slug']);
        }
        
        $this->disableLocalCSRFProtection();

	  	$this->validatorSchema->setPostValidator(
	  				new sfValidatorDoctrineUnique(array('model'=>$this->getModelName(), 'column'=>array('slug','listing_id')),
	  											  array('invalid'=>'An item with that URL identifier already exists')));
  	}
  
	public function updateObject($values = null) 
	{
        parent::updateObject($values);
        
		// i18n
		$culture = sfContext::getInstance()->getUser()->getCulture();
		$vars = $this->getObject()->Translation->getTable()->getColumns();
		unset($vars['id'], $vars['lang']);
		
		foreach ($vars as $var => $type) 
		{
		  $this->object->Translation[$culture]->set($var, $this->getValue($var));
		}
		
		$this->object->Translation[$culture]->set('lang', $culture);
        
    }

    public function updateDefaultsFromObject() 
	{
        parent::updateDefaultsFromObject();
        
    		// i18n
		$culture = sfContext::getInstance()->getUser()->getCulture();
		$vars = $this->getObject()->Translation->getTable()->getColumns();
		unset($vars['id'], $vars['lang']);

		if (isset($this->object->Translation[$culture])) 
		{
			foreach ($vars as $var => $pties) 
			{
				$this->setDefault($var, $this->object->Translation[$culture]->get($var));
			}
		}
    }
  	
	protected function doSave($con = null) 
	{
      parent::doSave($con);
      
      // :HACK: Because automatically sluggable, gets set as empty
      // and thus looks like it's been deleted on the form
      // so we need to set it as if it's one of the form values
      $this->setTaintedValue('slug', $this->object->get('slug'));
    }
    
    /**
     * :HACK: See doSave()
     *
     * @param string $field
     * @param string $value
     */
    private function setTaintedValue($field, $value) 
	{
      if ($this->isBound && empty($this->taintedValues[$field])) 
	  {
        $this->taintedValues[$field] = $value;
      }
    }
}
