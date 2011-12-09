<?php

/**
 * ListingItem filter form.
 *
 * @package    site_cms
 * @subpackage filter
 * @author     Jo Carter
 * @version    SVN: $Id: sfDoctrineFormFilterTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
abstract class PluginListingItemFormFilter extends BaseListingItemFormFilter 
{
	public function setup() 
	{
		parent::setup();
		
		$this->widgetSchema['title'] = new sfWidgetFormInput();
		$this->validatorSchema['title'] = new sfValidatorString(array('required'=>false));
		
  		$manager = listingManager::getInstance();
  		$choices = $manager->getItemStatusList($this->options['type']);
		$defn = $manager->getTypeDefinition($this->options['type']); 
		
		if (!isset($defn['use_categories']) || true === $defn['use_categories']) 
		{
			$query = ListingCategoryTable::getInstance()->createQuery('c')->where('c.listing_id = ? AND c.is_active = ?', array($this->options['listing_id'], true))->innerJoin('c.Translation t')->orderBy('t.title');
			$this->widgetSchema['listing_category_id'] = new sfWidgetFormDoctrineChoice(array('model' => $this->getRelatedModelName('ListingCategory'), 'add_empty' => true, 'query'=>$query));
			$this->widgetSchema->setLabel('listing_category_id', 'Category');
			
			$this->useFields(array('title', 'is_active', 'listing_category_id', 'status'));
		}
		else 
		{
			$this->useFields(array('title', 'is_active', 'status'));
		}
  		
		if (!empty($choices)) 
		{
			$statuses = array('' => '&nbsp;') + $choices;
			$this->widgetSchema['status'] = new sfWidgetFormChoice(array('choices'=>$statuses));
		}
		else unset($this['status']);
		
   	 	$this->getWidgetSchema()->setLabel('is_active', 'Is live?');
    	$this->getWidgetSchema()->setNameFormat('filter[%s]');
    	
    	$this->disableLocalCSRFProtection();
	}
	
	
	public function addFiltersToQuery($query) 
	{
		$filter = $this->getValues();
		
	  	if ('' != $filter['is_active']) 
		{
        	$query->addWhere('i.is_active = ?', array((bool)$filter['is_active']));
    	}
    	
    	if ('' != $filter['status']) 
		{
    		$query->addWhere('i.status = ?', array($filter['status']));
    	}
    
    	if (!empty($filter['title'])) 
		{
    	  	$query->addWhere('t.title LIKE ?', array('%'.$filter['title'].'%'));
    	}
    	
    	if (!empty($filter['listing_category_id'])) 
		{
    		$query->addWhere('i.listing_category_id = ?', array($filter['listing_category_id']));
    	}
    
		return $query;
	}
}
