<?php

/**
 * ListingCategory form.
 *
 * @package    site_cms
 * @subpackage form
 * @author     Jo Carter
 * @version    SVN: $Id: sfDoctrineFormTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
abstract class PluginListingCategoryForm extends BaseListingCategoryForm 
{
  	public function setup() 
	{
  		parent::setup();
  		
  		$this->widgetSchema['title'] = new sfWidgetFormInput();
  		$this->validatorSchema['title'] = new sfValidatorString(array('required'=>true));
  		
  		$this->widgetSchema['listing_id'] = new sfWidgetFormInputHidden();
  		
  		$this->useFields(array('id', 'title', 'listing_id', 'is_active'));
  		
  		$this->disableLocalCSRFProtection();
  	}
  	
	public function updateObject($values = null) 
	{
		parent::updateObject($values);
		
		// i18n
		$culture = sfContext::getInstance()->getUser()->getCulture();
		$vars = $this->getObject()->Translation->getTable()->getColumns();
		unset($vars['id'], $vars['lang']);
		
		foreach ($vars as $var => $ptiess) 
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
}
