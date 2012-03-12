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
   * Some restrictions can be applied to template definitions, 
   * including using a separate layout or only allowing a template for a specific route_name/site
    
   * Examples:
    
       homepage:
         name:       Homepage
         layout:     homeLayout
         restricted: true
         route_name: homepage   # with restricted: true only returns this template
         site:       gb
   */
  public function getPossibleTemplatesForPage($page) 
  {
    $templates = $this->getTemplateDefinitions();
    $out       = array();
    
    if ($page) 
    {
      $currentTemplate  = $page->template;
      $currentRouteName = $page->Sitetree->route_name;
    }
    else 
    {
      $currentTemplate  = false;
      $sitetree         = siteManager::getInstance()->getCurrentSitetreeNode();
      $currentRouteName = ($sitetree ? $sitetree->route_name : false);
    }
    
    $currentSite = siteManager::getInstance()->getCurrentSite();
    
    foreach ($templates as $templateSlug => $definition) 
    {
      $skip = ($templateSlug === $currentTemplate); // if we already have this template set allow it
      
      // Check whether allowed site for this template
      $sites = (isset($definition['site']) ? (is_array($definition['site']) ? $definition['site'] : array($definition['site'])) : array());
      
      if (!empty($sites) && !in_array($currentSite, $sites) && !$skip) continue;
      
      // Check whether route name restriction
      $templateRoutes = (isset($definition['route_name']) ? (is_array($definition['route_name']) ? $definition['route_name'] : array($definition['route_name'])) : array());
      
      // If restricted to a certain route name, then only return that one template
      if (isset($definition['restricted']) && true == $definition['restricted'] 
            && isset($definition['route_name']) && in_array($currentRouteName, $templateRoutes)
            && !$skip)
      {
        $out = array();
        $out[$templateSlug] = $definition['name'];
        return $out;
      }
      
      // If not restricted, but template only for certain routes
      if (!empty($templateRoutes) && !in_array($currentRouteName, $templateRoutes)) continue;
      
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
   * @param string $template
   * @return array
   */
  public function getTemplateBlockDefinitions($template) 
  {
    if (!$defn = $this->getTemplateDefinition($template)) 
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
    // check for site specific template version
    $siteVersion = sprintf('%s/%s/%s.php', $this->getTemplateDir(), siteManager::getInstance()->getCurrentSite(), $this->getTemplateName($templateSlug));
    
    if (is_file($siteVersion)) return $siteVersion;
    else return sprintf('%s/%s.php', $this->getTemplateDir(), $this->getTemplateName($templateSlug));
  }
  
  /**
   * Get the name of the template - allows sharing of templates by adding template: TEMPLATE_NAME in config
   *
   * @param string $templateSlug
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