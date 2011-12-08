<?php

/**
 * Page form.
 *
 * @package    site_cms
 * @subpackage form
 * @author     Jo Carter
 * @version    SVN: $Id: sfDoctrineFormTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
abstract class PluginPageForm extends BasePageForm 
{
	public function setup() 
	{
		parent::setup();
		
		$this->useFields(array('template'));
		
		$this->widgetSchema->setLabel('template', 'Template <em>*</em>');
		
		$Page = $this->getObject();
	    $contentManager = pageManager::getInstance();
	    $possibleTemplates = $contentManager->getPossibleTemplatesForPage($Page);
	
	    $this->widgetSchema['template'] = new sfWidgetFormChoice(array('choices' => array('' => '&nbsp;') + $possibleTemplates));
	    $this->validatorSchema['template'] = new sfValidatorChoice(array('choices' => array_keys($possibleTemplates), 'required' => true));
    }
}
