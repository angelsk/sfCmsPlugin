<?php
class ContentBlockTypeHTMLForm extends ContentBlockTypeForm 
{
	/**
	 * Set up the field
	 * 
	 * @see ContentBlockType/ContentBlockTypeForm::configure()
	 */
	public function configure() 
	{
		parent::configure(); 
		
		$this->widgetSchema['value'] = new sfWidgetFormTextareaTinyMCE(array('width'=>500, 'config'=>$this->getConfig()));
		$this->validatorSchema['value'] = new enhancedValidatorString($this->getValidatorOptions(), array('max_length'=>'Character limit of %max_length% characters exceeded. Text was %current_length% characters long (excluding HTML markup)'));
		$this->widgetSchema->setLabel('value','&nbsp;');
	}
	
	/**
	 * Set up TinyMCE 
	 */
	protected function getConfig() 
	{
      	$culture = sfContext::getInstance()->getUser()->getCulture();
      	$tinyMceConfig = sfConfig::get('app_site_htmlBlock', false);
      	$tinyMceInitExtra = '';
      	$siteEnabledPlugins = '';
      	$sitePlugins = '';
      
      	if (is_array($tinyMceConfig)) 
		{
      		$tinyMceInitExtra = (isset($tinyMceConfig['tiny_mce_extra'])) ? $tinyMceConfig['tiny_mce_extra'] : false;
      		$siteEnabledPlugins = (isset($tinyMceConfig['tiny_mce_plugins'])) ? $tinyMceConfig['tiny_mce_plugins'] : array();
      		$sitePlugins = (!empty($siteEnabledPlugins)) ? ','.implode(',', $siteEnabledPlugins) : '';
      	}
      	
      	if (false !== $tinyMceInitExtra) $tinyMceInitExtra = ','.$tinyMceInitExtra;
              
		$tinyMceSettings = '
				  language: "'.strtolower(substr($culture, 0, 2)).'",
				  plugins: "table,advimage,advlink,paste' . $sitePlugins . '",
				  theme_advanced_path_location: "bottom",
				  theme_advanced_buttons1: "formatselect,justifyleft,justifycenter,justifyright,justifyfull,separator,bold,italic,strikethrough,separator,sub,sup,separator,charmap,pasteword' . $sitePlugins . '",
				  theme_advanced_buttons2: "bullist,numlist,separator,outdent,indent,separator,undo,redo,separator,link,unlink,image,separator,cleanup,removeformat,separator,code",
				  theme_advanced_buttons3: "tablecontrols",
				  theme_advanced_blockformats : "p,h1,h2,h3,h4",
				  extended_valid_elements: "img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name]",
				  relative_urls: false,
				  debug: false 
				  ' . $tinyMceInitExtra;
		
	    return $tinyMceSettings;
	}
	
	/**
	 * Get validation from the config
	 */
	protected function getValidatorOptions()
	{
		$definition = $this->getObject()->getContentBlockVersion()->getDefinition();
    	$validatorOptions = array();
    	
    	if (isset($definition['character_limit'])) 
		{
      		$validatorOptions['max_length'] = (int)$definition['character_limit'];
    	}
    
    	if (isset($definition['required'])) 
		{
      		if (true == $definition['required']) 
			{
        		$validatorOptions['required'] = true;
      		}
      		else $validatorOptions['required'] = false;
    	}
    	else $validatorOptions['required'] = false;
    
    	return $validatorOptions;
	}
	
	/**
	 * Link to the Content block 
	 */
	public function getModelName() 
	{
    	return 'ContentBlockTypeHTML';
  	}
}