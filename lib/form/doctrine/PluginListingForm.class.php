<?php

/**
 * Listing form.
 *
 * @package    site_cms
 * @subpackage form
 * @author     Jo Carter
 * @version    SVN: $Id: sfDoctrineFormTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
abstract class PluginListingForm extends BaseListingForm
{

	public function setup()
	{
		parent::setup();

		$this->useFields(array('template', 'use_custom_order', 'results_per_page', 'rss_url'));

		$this->widgetSchema->setLabel('use_custom_order', 'Manually order items');
		$this->widgetSchema->setLabel('results_per_page', 'Results per page <em>*</em>');
		$this->widgetSchema->setLabel('template', 'Listing template <em>*</em>');
		$this->widgetSchema->setLabel('rss_url','External RSS url (e.g: from Feedburner)');

		$listing = $this->getObject();
		$manager = listingManager::getInstance();
		$choices = $manager->getPossibleTemplatesForListing($listing);
		
		$this->widgetSchema['template'] = new sfWidgetFormChoice(array('choices' => array('' => '&nbsp;') + $choices));
		$this->validatorSchema['template'] = new sfValidatorChoice(array('choices' => array_keys($choices), 'required' => true));
	}
}
