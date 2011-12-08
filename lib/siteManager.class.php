<?php
/**
 * Class that handles the site and sitetree
 * 
 * @author Jo Carter <work@jocarter.co.uk>
 */
class siteManager 
{
  /**
   * Current version of the plugin
   * @var string
   */
  const VERSION = '1.0';
  
  /**
   * The current instance of the manager
   *
   * @var siteManager
   */
  protected static $instance;

  /**
   * The node where we are in the sitetree at the moment
   *
   * @var Sitetree
   */
  protected $currentSitetree = null;

  /**
   * Ancestors of where we are in the sitetree at the moment
   *
   * @var Doctrine_Collection
   */
  protected $currentSitetreeAncestors = null;

  /**
   * Stored module definitions - used by the backend
   *
   * @var array
   */
  protected $moduleDefinitions = null;

  /**
   * Stored core navigation
   */
  protected $coreNavigation = null;

  /**
   * @var sfFileCache
   */
  protected $cache;
  
  /**
   * @var boolean $renderFromRequest
   */
  protected $renderFromRequest = false;
  
  /**
   * Get the current version
   */
  public function getVersion()
  {
     return self::VERSION;
  }
  
  /**
   * Register the routes created by the sitetree module
   * 
   * Called by projectConfiguration
   * 
   * @param sfEvent $event
   */
  static public function routingLoadConfigurationListener(sfEvent $event) 
  {
    $manager = self::getInstance();
    $app = sfContext::getInstance()->getConfiguration()->getApplication();
    
    if ($manager->getManagedApp() != $app) 
    {
      // we are not managing routing for this app
      return;
    }
    
    $router = $event->getSubject();
    $manager->registerRoutes($router);
  }
  
  /**
   * Get the current instance of the manager
   *
   * @return siteManager
   */
  public static function getInstance() 
  {
    if (!self::$instance) 
    {
      $class = sfConfig::get('app_site_manager_class', 'siteManager');

      self::$instance = new $class();
    }
    
    return self::$instance;
  }
  
  /**
   * Check site set up correctly
   */
  public function __construct() 
  {
    if (!$v = $this->getManagedApp()) 
    {
      throw new sfException("Managed app config not present");
    }
    
    if (!is_array($this->getAvailableModules())) 
    {
      throw new sfException("Available modules not set");
    }
    
    if (!is_array($this->getSite())) 
    {
      throw new sfException("Site definition not set");
    }
  }
  
  /**
   * The app we're managing routing for, ie. the frontend
   *
   * @return string
   */
  public function getManagedApp() 
  {
    return sfConfig::get('app_site_managed_app', 'frontend');
  }
  
  /**
   * Get the modules enabled for this site.
   */
  public function getAvailableModules() 
  {
    return sfConfig::get('app_site_available_modules');
  }
  
  /**
   * Get the default site
   *
   * @return string
   */
  public function getDefaultSite() 
  {
    return sfConfig::get('app_site_default_site');
  }
  
  /**
   * Get the current site we're on (managed by ysfDimensions if multiple sites)
   *
   * @return string
   */
  public function getCurrentSite() 
  {
    $config = sfContext::getInstance()->getConfiguration();
      
    if (class_exists('ysfApplicationConfiguration') && $config instanceof ysfApplicationConfiguration)
    {
      // We are dealing with a multi-site app.
      if (!$config instanceof ysfApplicationConfiguration)
      {
        throw new sfException("Config must be an instance of ysfApplicationConfiguration");
      }
      
      return $config->getDimension()->get('site');
    }
    else
    {
      // single-site app... use the default site
      return $this->getDefaultSite();
    }
  }
  
  /**
   * Get curent site configuration
   * 
   * @return array
   */
  public function getSite() 
  {
    return sfConfig::get('app_site_definition');
  }
  
  /**
   * Get the default culture for the site
   *
   * @return string
   */
  public function getDefaultCulture() 
  {
    $defn = $this->getSite();
    return $defn['default_culture'];
  }
  
  /**
   * Site cache
   *
   * @return sfFileCache
   */
  public function getCache() 
  {
    if (!$this->cache) 
    {
      $cacheDir = sfConfig::get('sf_cache_dir') . '/' . $this->getManagedApp() . '/' . sfConfig::get('sf_environment') . '/site';
      $this->cache = new sfFileCache(array('cache_dir' => $cacheDir));
    }
    
    return $this->cache;
  }
  
  /**
   * Gets called when the sitetree is changed.
   *
   * It clears some caches
   */
  public function sitetreeChanged() 
  {
    $this->clearManagedAppRoutingCache();
    $this->clearCrossAppCache();
  }

  /**
   * Clear managed app's routing cache
   */
  public function clearManagedAppRoutingCache() 
  {
    $currentConfig = sfContext::getInstance()->getConfiguration();

    $managedAppConfig = ProjectConfiguration::getApplicationConfiguration(
      $this->getManagedApp(),
      $currentConfig->getEnvironment(),
      $currentConfig->isDebug()
    );
    
    $this->clearRoutingCache($managedAppConfig);
    
    if (sfConfig::get('sf_logging_enabled')) 
    {
      sfContext::getInstance()->getLogger()->info('Cleared frontend cache');
    }
  }

  /**
   * Clear routing cache.
   *
   * This is taken from sfCacheClearTask
   *
   * @param sfApplicationConfiguration $appConfiguration
   */
  protected function clearRoutingCache(sfApplicationConfiguration $appConfiguration) 
  {
    $app = $appConfiguration->getApplication();
    $env = $appConfiguration->getEnvironment();

    $config = sfFactoryConfigHandler::getConfiguration($appConfiguration->getConfigPaths('config/factories.yml'));

    $class = $config['routing']['param']['cache']['class'];
    $parameters = $config['routing']['param']['cache']['param'];

    $cache = new $class($parameters);
    $cache->clean();
  }

  /**
   * Clear cross app link cache
   */
  public function clearCrossAppCache() 
  {
    $this->getCache()->removePattern('ca.*');
  }

  /**
   * Get the routing proxy
   *
   * @param sfPatternRouting $router
   * @return siteRoutingProxy
   */
  public function getRoutingProxy($router = null) 
  {
    $class = sfConfig::get('app_site_routing_proxy_class', 'siteRoutingProxyImpl');
    $proxy = new $class;

    if ($router !== null) 
    {
      $proxy->setRouter($router);
    }

    return $proxy;
  }
  
  /**
   * Register routes into the current application
   *
   * @param sfPatternRouting $router
   */
  public function registerRoutes($router) 
  {
    $routingProxy = $this->getRoutingProxy($router);
    $site         = $this->getCurrentSite();
    
    $sitetrees    = SitetreeTable::getInstance()->getSitetreeNodes($site, Doctrine::HYDRATE_RECORD, false);
    $junkChar     = $this->getRouteJunkChar();

    $urlStack = array();
    
    foreach ($sitetrees as $sitetree) 
    {
      // Add in the routes one by one
      $urlStack[$sitetree->level] = $sitetree->base_url;

      // Are we using a custom routing handler?
      $moduleDefinition = $this->getModuleDefinition($sitetree->target_module);
      
      if (isset($moduleDefinition['use_custom_routing']) && $moduleDefinition['use_custom_routing']) 
      {
        $event = new siteEvent(
          $sitetree,
          siteEvent::SITETREE_ROUTING,
          array('routingProxy' => $routingProxy, 'urlStack' => $urlStack)
        );
        
        $sitetree->dispatchSiteEvent($event);
      } 
      else 
      {
        sitetree::addToRouting($sitetree, $routingProxy, $junkChar, $urlStack);
      }
    }
    
    if (sfConfig::get('sf_logging_enabled')) 
    {
       sfContext::getInstance()->getLogger()->info('Registered dynamic routes');
    }
  }
  
  /**
   * Get the "junk" char for routes.  This must be a character not allowed in the
   * routenames (see createSitetreeForm - only a-z0-9-).  It is used for when we have to generate multiple routes
   * for one Sitetree entry, eg. for greedy/i18n routes.
   *
   * @return string
   */
  public function getRouteJunkChar() 
  {
    return '_';
  }
  
  /**
   * Generate a cross app url for the given internal url
   * 
     * @param string $url Internal url from the target app
     * @param string $app The target app
     * @param string $env The environment we're in
     *
     * @return string
     */
  public function generateCrossAppUrlFor($url, $app = null, $env = null) 
  {
    // this holds copies of the other contexts so we don't have to keep loading them
    static $otherContexts = array();
      
    if ($app === null) 
    {
      $app = $this->getManagedApp();
    }
      
    if ($env === null) 
    {
      $env = sfConfig::get('sf_environment');
    }
    
    $debug = sfConfig::get('sf_debug');
    
    // get current config+context so we can switch back after
    $currentApp = sfConfig::get('sf_app');
    
    // See if we saved this link in the cache
    $cache = $this->getCache();
    $cacheUrl = str_replace(array('+', '?', '=', '@'), '', $url);
    
    $cacheKey = "ca.$app.$env.$cacheUrl";
    
    if ($cache->has($cacheKey)) 
    {
      return $cache->get($cacheKey);
    }

    if (!isset($otherContexts[$app][$env])) 
    {
      // get config/context for our other app.  This will switch the current
      // context and change the contents of sfConfig, so we will need to change back after
      $otherConfiguration = ProjectConfiguration::getApplicationConfiguration($app, $env, $debug);
      $otherContexts[$app][$env] = sfContext::createInstance($otherConfiguration, $app . $env);
    } 
    else 
    {
      // we already initialised the other context, switch to it now
      sfContext::switchTo($app . $env);
    }

    try 
    {
      // make the url
      $generatedUrl = $otherContexts[$app][$env]->getController()->genUrl($url, true);
    } 
    catch (sfConfigurationException $e) 
    {
      $generatedUrl = '';
    }
    
    // to deal with the case where we have a different domain name, we have a couple
    // of ways to manipulate the generated urls.  Either by adding on a prefix or
    // by using a function to alter them:
    $generatedUrl = $this->processCrossAppUrl($generatedUrl, $currentApp, $app, $env);

    // switch back to old config
    sfContext::switchTo($currentApp);

    // save
    $cache->set($cacheKey, $generatedUrl);

    return $generatedUrl;
  }
  
  /**
   * Process the cross app url generated by the other app
   *
   * This allows us to do horrible things like add and remove the "admin." from
   * the beginning of the urls
   *
   * @param string $generatedUrl
   * @param string $fromApplication
   * @param string $toApplication
   * @param string $environment
   * @return string
   */
  public function processCrossAppUrl($generatedUrl, $fromApplication, $toApplication, $environment) 
  {
    if ($toApplication == $this->getManagedApp())
    {
      // If the url returned doesn't already contain http:// - because of site magic in the front web controller
      if (false !== strpos($generatedUrl, 'http://')) 
      {
        // Absolute URL, but still has admin. in
        if (false !== strpos($generatedUrl, 'admin.')) 
        {
          // If we're on prod, and don't already have www. in the URL
          if ('prod' == $environment && false === strpos($generatedUrl, 'www.')) 
          {
            $generatedUrl = str_replace('admin.', 'www.', $generatedUrl);
          }
          else 
          {
            $generatedUrl = str_replace('admin.', '', $generatedUrl);
          }
        }
        // Or has the controller in the URL
        else if (false != strpos($generatedUrl, $fromApplication.'.php/') || false != strpos($generatedUrl, $fromApplication.'_'.$environment.'.php/'))
        {
          $fromController   = ('prod' == $environment) ? $fromApplication.'.php' : $fromApplication.'_'.$environment.'.php';
          $toController     = ('prod' == $environment) ? '' : $toApplication.'_'.$environment.'.php';
          $generatedUrl     = str_replace($fromController, $toController, $generatedUrl);
        }
        
        return $generatedUrl;
      }
      else 
      {
        // need to add in the hostname without the "admin." (if appropriate)
        $hostName   = $_SERVER['HTTP_HOST'];
        $hostName   = str_replace('admin.', '', $hostName);
        return 'http://' . $hostName . $generatedUrl;
      }
    }
    else 
    {
      throw new sfException("Unknown app: " . $toApplication);
    }
  }
  
  /**
   * Get the definitions for all the available modules - these are always cached.
   *
   * @return array
   */
  public function getModuleDefinitions() 
  {
    if ($this->moduleDefinitions === null) 
    {
      $loadedFromCache = false;
      $sfContext = sfContext::getInstance();
      $cache = $this->getCache();
      
      if ($cache->has('module_definitions')) 
      {
        $this->moduleDefinitions = unserialize($cache->get('module_definitions'));
        $loadedFromCache = true;
        
        if (sfConfig::get('sf_logging_enabled')) 
        {
          $sfContext->getLogger()->info('Loaded module definitions from cache');
        }
      }

      // if we have not got the definitions from the cache search the disk for them
      if (!$loadedFromCache) 
      {
        $this->moduleDefinitions = $this->determineModuleDefinitions();
        
        if (sfConfig::get('sf_logging_enabled')) 
        {
          $sfContext->getLogger()->info('Determined module definitions from disk');
        }
        
        $cache->set('module_definitions', serialize($this->moduleDefinitions), 86400);
      }
    }
    
    return $this->moduleDefinitions;
  }
  
  /**
   * Get the definition for a specific module
   *
   * @param string $module
   * @return mixed The module definition array or null if no such module exists.
   */
  public function getModuleDefinition($module) 
  {
    $definitions = $this->getModuleDefinitions();
    
    if (!isset($definitions[$module])) 
    {
      return null;
    } 
    else 
    {
      return $definitions[$module];
    }
  }
  
  /**
   * Search for module definitions in the module.yml files of all modules.
   *
   * @return array
   */
  public function determineModuleDefinitions() 
  {
    // get a list of the files
    $files = array();

    // this assumes the usual naming conventions for folders
    if ($others = glob(sfConfig::get('sf_apps_dir') . "/*/modules/*/config/module.yml")) 
    {
      $files = array_merge($files, $others);
    }
    
    if ($others = glob(sfConfig::get('sf_plugins_dir').'/*/modules/*/config/module.yml')) 
    {
      $files = array_merge($files, $others);
    }

    $moduleDefinitions = array();
    
    foreach ($files as $file) 
    {
      try 
      {
        // extract module name from file
        $matches = array();
        $match = preg_match('~([^/]+)/config/module\.yml~', $file, $matches);
        
        if (!$match) 
        {
          continue;
        }
        
        $moduleName = $matches[1];

        // load module.yml
        $config = sfYamlConfigHandler::parseYaml($file);
        $config = sfYamlConfigHandler::flattenConfigurationWithEnvironment($config);

        // look for our module definition in there
        if (!@$config['site']['module_definition']) 
        {
          // no module definition here
          continue;
        }
        
        if (!$this->checkModuleDefinition($config['site']['module_definition'])) 
        {
          // no module definition here
          continue;
        }
        
        $moduleDefinitions[$moduleName] = $config['site']['module_definition'];
      } 
      catch (Exception $e) 
      {
        // do nothing if we fail
      }
    }
    
    return $moduleDefinitions;
  }
  
  /**
   * Check that the given module definition makes sense.
   *
   * @param array $definition
   * @return boolean
   */
  public function checkModuleDefinition($definition) 
  {
    if (isset($definition['name'])) 
    {
      return true;
    }
    else 
    {
      return false;
    }
  }
  
  /**
   * Get the sitetree node matched by the routing in the current request, if there is one.
   *
   * This used by modules to find out where they are in the site tree.
   *
   * Returns null if none matches.
   *
   * @param sfContext $context
   * @return Sitetree
   */
  public function getSitetreeNodeFromContext($context) 
  {
    // first we need the route name of the route which was matched the current request
    $routing = $context->getRouting();
    
    if (!$routing instanceof sfPatternRouting) 
    {
      throw new sfException("Only compatible with routing of class sfPatternRouting.");
    }
    
    $routeName = $routing->getCurrentRouteName();
    
    if (sfConfig::get('sf_logging_enabled')) 
    {
       $context->getLogger()->info('Found routename: ' . $routeName);
    }
    
    $site = $this->getCurrentSite();

    return $this->getRoutingProxy()->getSitetreeFromSymfonyRoute($routeName, $site);
  }
  
  /**
   * This is called in the frontend app so it can see where it is in the sitetree.
   *
   * If a sitetree has not been set manually it will look one up from the routing.
   *
   * @return Sitetree
   */
  public function getCurrentSitetreeNode() 
  {
    if (!$this->currentSitetree) 
    {
      $this->currentSitetree = $this->getSitetreeNodeFromContext(sfContext::getInstance());
    }
    
    return $this->currentSitetree;
  }
  
  /**
   * Set where we are in the sitetree.
   *
   * @param Sitetree $currentSitetree
   */
  public function setCurrentSitetreeNode($currentSitetree) 
  {
    $this->currentSitetree = $currentSitetree;
  }
  
  /**
   * This is called in the frontend app so it can see where it is in the sitetree.
   *
   * If a sitetree has not been set manually it will look one up from the routing
   * and add in things like http metas.
   *
   * @return Sitetree
   */
  public function initCurrentSitetreeNode() 
  {
    if (!$sitetree = $this->getCurrentSitetreeNode()) 
    {
      return null;
    }

    $context = sfContext::getInstance();
    $response = $context->getResponse();
    $culture = $context->getUser()->getCulture();

    if ($v = $sitetree->Translation[$culture]->html_keywords) 
    {
      $response->addMeta('keywords', htmlentities($v, null, 'utf-8', false), true, false);
    }
    
    if ($v = $sitetree->Translation[$culture]->html_description) 
    {
      $response->addMeta('description', htmlentities($v, null, 'utf-8', false), true, false);
    }
    
    if ($v = $sitetree->Translation[$culture]->html_title) 
    {
      $response->setTitle(htmlentities($v, null, 'utf-8', false), false);
    } 
    
    else if ($v = $sitetree->Translation[$culture]->title) 
    {
      $response->setTitle(htmlentities($v, null, 'utf-8', false), false);
    }

    return $sitetree;
  }
  
  /**
   * This is called in the frontend app so it can see where it is in the sitetree.
   *
   * If a sitetree has not been set manually it will look one up from the routing.
   *
   * @return Doctrine_Collection
   */
  public function getCurrentSitetreeAncestors() 
  {
    if (!$this->currentSitetreeAncestors) 
    {
      if ($currentSitetree = $this->getCurrentSitetreeNode()) 
      {
        sitetreeTable::getInstance()->setTreeQueryWithTranslation();
        $this->currentSitetreeAncestors = $currentSitetree->getNode()->getAncestors();
        sitetreeTable::getInstance()->resetTreeQuery();
      } 
      else 
      {
        $this->currentSitetreeAncestors = null;
      }
    }
    
    return $this->currentSitetreeAncestors;
  }
  
  /**
   * Get the entire sitetree for a site
   *
   * This also creates the site root node if it does not exist
   *
   * @param string $site
   * @param boolean $createRootIfNotExist Try and create root node if it doesn't exist?
   * @param boolean $includeTranslations Include translatable content - e.g: title
   * @param const $hydrationMode Return as array or record
   * @return Doctrine_Collection
   */
  public function getEntireSitetree($site, $createRootIfNotExist = true, $includeTranslations = true, $hydrationMode = Doctrine::HYDRATE_RECORD) 
  {
    if ($includeTranslations) 
    {
      $treeObject = SitetreeTable::getInstance()->setTreeQueryWithTranslation();
    }
    else 
    {
      $treeObject = SitetreeTable::getInstance()->getTree();
    }

    $results = $treeObject->fetchTree(array('root_id' => $site), $hydrationMode);

    if ($includeTranslations) 
    {
      $treeObject->resetBaseQuery();
    }

    // Results is the collection of objects or false of there is no tree (ie. no root node)
    if ($results || !$createRootIfNotExist) 
    {
      return $results;
    }

    // we are missing a root node for this site, try and create one:
    $defn = $this->getSite();
    $rootModule = isset($defn['root_module']) ? $defn['root_module'] : 'default';
    $rootNode = Sitetree::createRoot($site, $rootModule);

    $results = new Doctrine_Collection('Sitetree');
    $results->add($rootNode);

    return $results;
  }
  
  /**
   * Get a list of live sitetree nodes in route_name=>title pairs for a dropdown etc.
   *
   * @return array
   */
  public function getSitetreeForForm($site = null, $level = null) 
  {
    if ($site === null) 
    {
      $site = $this->getCurrentSite();
    }
    
    $tree = SitetreeTable::getInstance()->getSitetree($site, $level, Doctrine::HYDRATE_ARRAY);
    $culture = sfContext::getInstance()->getUser()->getCulture();

    foreach ($tree as $item) 
    {
      $out[$item['route_name']] = str_repeat(':: ', $item['level']) . @$item['Translation'][$culture]['title'];
    }
    
    return $out;
  }
  
  /**
   * Get sitetree nodes marked as core navigation - cached so that user can configure but not high load on site
   */
  public function getCoreNavigation()
  {
    if (null === $this->coreNavigation) 
    {
      $cache = $this->getCache();
      $loadedFromCache = false;
      $this->coreNavigation = array();
        
      if ($cache->has('ca.core_navigation')) 
      {
        $rawCoreNavigation = unserialize($cache->get('ca.core_navigation'));
        
        foreach ($rawCoreNavigation as $sitetreeArray) 
        {
          $sitetree = new Sitetree();
          $sitetree->fromArray($sitetreeArray);
          $this->coreNavigation[] = $sitetree;
        }
        
        $loadedFromCache = true;
          
        if (sfConfig::get('sf_logging_enabled')) 
        {
           sfContext::getInstance()->getLogger()->info('Loaded core navigation from cache');
        }
      }
      
      if (!$loadedFromCache) 
      {
        $rawCoreNavigation = SitetreeTable::getInstance()->getCoreNavigation();
        $cachedCoreNavigation = array();
        
        foreach ($rawCoreNavigation as $sitetree) 
        {
          $cachedCoreNavigation[] = $sitetree->toArray();
          $this->coreNavigation[] = $sitetree;
        }
        
        $cache->set('ca.core_navigation', serialize($cachedCoreNavigation), 86400);
      }
    }
    
    return $this->coreNavigation;
  }
  
  /**
   * Should we be trying to render content blocks from the request?
   *
   * @param boolean $v
   */
  public function setRenderFromRequest($v) 
  {
    $this->renderFromRequest = $v;
  }
  
  /**
   * Are we rendering content blocks from the request?
   *
   * @return boolean
   */
  public function getRenderFromRequest() 
  {
    return $this->renderFromRequest;
  }
}