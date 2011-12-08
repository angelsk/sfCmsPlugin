<?php 
/**
 * This provides overall config/management for the page module.
 *
 * To alter functionality it can be extended, and a new value for
 * "app_site_page_manager_class" can be used.
*/
class pageManager 
{
	/**
	 * Current instance
	 *
	 * @var pageManager
	 */
	protected static $instance;
	
	/**
	 * Get an instance of the manager
	 *
	 * @return pageManager
	 */
	public static function getInstance() 
	{
		if (!self::$instance) 
		{
			$class = sfConfig::get('app_site_page_manager_class', 'pageManager');
			
			if (!class_exists($class)) 
			{
				throw new sfException("Could not make a config manager, class '$class' did not exist.  Check app_site_page_manager_class in your app.yml");
			}
			
			self::$instance = new $class();
		}
		
		return self::$instance;
	}
	
	/**
	 * Get a list of the template names we can use for this page
	 * 
	 * Some restrictions can be applied to template definitions, including using a separate layout or only allowing a template for a specific route_name
	 
	 * Examples:
	 
	   homepage:
        name: Homepage
        layout: homeLayout
        restricted: true
	 */
	public function getPossibleTemplatesForPage($page) 
	{
	    $templates = $this->getTemplateDefinitions();
	    $out = array();
	    
	    if ($page) 
		{
		    $currentTemplate = $page->template;
		    $currentRouteName = $page->Sitetree->route_name;
	    }
	    else {
	    	$currentTemplate = false;
	    	$currentRouteName = siteManager::getInstance()->getCurrentSitetreeNode()->route_name;
	    }
	    
	    foreach ($templates as $templateSlug => $definition) 
		{
	    	// If restricted, then only return that one template
	    	if (isset($definition['restricted']) && true == $definition['restricted'] && isset($definition['route_name']) 
	    	    && ((is_array($definition['route_name']) && in_array($currentRouteName, $definition['route_name']))
	    	      || (!is_array($definition['route_name']) && $currentRouteName == $definition['route_name']))) 
			{
	    	  $out = array();
	    	  $out[$templateSlug] = $definition['name'];
	    	  return $out;
	    	}
	    	
	    	$out[$templateSlug] = $definition['name'];
	    }
	    
		asort($out);
		return $out;
	}
	
	/**
	 * Get the template definitions from config
	 *
	 * @return array
	 */
	public function getTemplateDefinitions() 
	{
		$templates = sfConfig::get('app_site_page_templates', array());
		
		if (!$templates) 
		{
		    throw new sfException("Templates missing from: app_site_page_templates");
		}
		
		return $templates;
	}
	
  	/**
   	 * Get a template definition for a particular template slug
  	 *
  	 * @param string $templateSlug
  	 * @return array
  	 */
	public function getTemplateDefinition($templateSlug) 
	{
    	$templates = $this->getTemplateDefinitions();
    	
    	if (!isset($templates[$templateSlug])) 
		{
        	throw new sfException("Missing template definitions");
    	}
    	
    	return $templates[$templateSlug];
	}
	
  	/**
  	 * Get the content block definitions for the template
  	 *
  	 * @param string $type
  	 * @return array
  	 */
  	public function getTemplateBlockDefinitions($type) 
	{
    	if (!$defn = $this->getTemplateDefinition($type)) 
		{
      		return array();
    	}
    
    	$blocks = (isset($defn['blocks']) ? $defn['blocks'] : array());
    
    	return $blocks;
  	}
	
	/**
	 * Get a template definition attribute
	 *
	 * @param string $templateSlug
	 * @param string $attribute
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	public function getTemplateDefinitionAttribute($templateSlug, $attribute, $defaultValue = null) 
	{
		$definition = $this->getTemplateDefinition($templateSlug);
		
		return (isset($definition[$attribute]) ? $definition[$attribute] : $defaultValue);
	}
	
	/**
	 * Get the location on the disk of a template file from its name
	 *
	 * @param string $name
	 * @param boolean $checkExists
	 * @return string
	 */
	public function getTemplateFileLocation($templateSlug) 
	{
		return $this->getTemplateDir() . DIRECTORY_SEPARATOR . $this->getTemplateName($templateSlug) . ".php";
	}
	
  	/**
  	 * Get the name of the template - allows sharing of templates by adding template: TEMPLATE_NAME in config
  	 *
  	 * @param $type string
  	 * @return string
   	 */
  	public function getTemplateName($templateSlug) 
	{
      	return $this->getTemplateDefinitionAttribute($templateSlug, 'template', $templateSlug);
  	}

	/**
	 * Get the directory where the templates are stored
	 *
	 * @return string
	 */
	public function getTemplateDir() 
	{
		return sfConfig::get('app_site_page_template_dir', sfConfig::get('sf_root_dir') . '/templates/page');
	}
}