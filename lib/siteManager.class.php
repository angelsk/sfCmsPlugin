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
   * This holds copies of the other contexts so we don't have to keep loading them
   * 
   * @var $otherContexts
   */
  protected static $otherContexts = array();

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
   * Get a list of active sites (if multiple sites set up)
   * 
   * This method should be overridden in a custom siteManager
   * if the sites require filtering by permissions for example.
   * 
   * @param mixed $filter A dummy param to use to pass through filters for custom implementations
   * @return array
   */
  public function getActiveSites($filter = null)
  {
    return sfConfig::get('app_site_active_sites', array());
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
    if (sfConfig::get('sf_app') == $this->getManagedApp())
    {
      $config = sfContext::getInstance()->getConfiguration();
      
      if (class_exists('ysfApplicationConfiguration') && $config instanceof ysfApplicationConfiguration)
      {
        // We are dealing with a multi-site app.
        if (!$config instanceof ysfApplicationConfiguration)
        {
          throw new sfException("Config must be an instance of ysfApplicationConfiguration");
        }
        
        return (!is_null($config->getDimension()) ? 
                                    $config->getDimension()->get('site') : 
                                    ($config->getApplication() == $this->getManagedApp() ? false : $this->getDefaultSite())); 
                                    // dimension not yet set - managed app, set false - other send default
      }
      else
      {
        // single-site app... use the site set in config
        return sfConfig::get('app_site_identifier');
      }
    }
    // We can set it in the session
    else
    {
      // Site config loaded in plugin initialize - pulls it from cookies and loads site config
      return sfConfig::get('app_site_identifier');
    }
  }

  /**
   * Set the current site we're on.
   *
   * ysfDimensionsPlugin has a bug with admin generated modules - so cannot be used in an
   * admin context
   *
   * @param string $site
   */
  public function setCurrentSite($site) 
  {
    // If cookie set for non-active site, then use default site
    $activeSites = $this->getActiveSites();
    if (!empty($activeSites)) $activeSites = array_keys($activeSites);
    
    if (!in_array($site, $activeSites)) $site = $this->getDefaultSite();
    
    if (sfConfig::get('sf_logging_enabled')) 
    {
      sfContext::getInstance()->getLogger()->info(sprintf('Setting current site to %s', $site));
    }
    
    // Save it in a cookie then it's remembered even when logged out by cache clearing on server
    $expiration_age = sfConfig::get('app_sf_guard_plugin_remember_key_expiration_age', 15 * 24 * 3600); // re-use this expiration :)
    sfContext::getInstance()->getResponse()->setCookie('site_' . sfConfig::get('sf_app'), $site, time() + $expiration_age);
  }
  
  /**
   * Load config for the current site dimension
   * 
   * @param string $site
   */
  public function loadSiteConfig($site)
  {
    // Load dimensions config (if it exists) - requires the config handler registered above
    if (file_exists(sprintf('%s/config/%s/app.yml', sfConfig::get('sf_root_dir'), $site)))
    {
      if ($file = ProjectConfiguration::getActive()->getConfigCache()->checkConfig(sprintf('config/%s/app.yml', $site), true))
      {
        include($file);
      }
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
  
  protected function getAppConfig($app)
  {
    $currentConfig = sfContext::getInstance()->getConfiguration();

    $appConfig = ProjectConfiguration::getApplicationConfiguration(
      $app,
      $currentConfig->getEnvironment(),
      $currentConfig->isDebug()
    );
    
    return $appConfig;
  }
  
  /**
   * Site cache
   * Use the same cache as the rest of the site
   *
   * @return sfCache
   */
  public function getCache() 
  {
    if (!$this->cache) 
    {
      // get current config+context so we can switch back after
      $currentApp = sfConfig::get('sf_app');
      
      // Switch config
      $managedAppConfig = $this->getAppConfig($this->getManagedApp());
      
      $config = sfFactoryConfigHandler::getConfiguration($managedAppConfig->getConfigPaths('config/factories.yml'));
      $cachedir = sprintf('%s/%s/%s/site', sfConfig::get('sf_cache_dir'), $this->getManagedApp(), sfConfig::get('sf_environment'));
      
      // switch back
      $currentConfig = $this->getAppConfig($currentApp);
      
      $class = $config['view_cache']['class'];
      $parameters = $config['view_cache']['param'];
      $parameters['prefix'] = $cachedir;
      $parameters['cache_dir'] = $cachedir;
      if ('sfMemcacheCache' == $class) $parameters['storeCacheInfo'] = true;
      
      try 
      {
        $this->cache = new $class($parameters);
      }
      catch (Exception $e) { }
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
    // get current config+context so we can switch back after
    $currentApp = sfConfig::get('sf_app');
    
    // Switch config
    $managedAppConfig = $this->getAppConfig($this->getManagedApp());
    
    $this->clearRoutingCache($managedAppConfig);
    
    // switch back
    $currentConfig = $this->getAppConfig($currentApp);
    
    if (sfConfig::get('sf_logging_enabled')) 
    {
      sfContext::getInstance()->getLogger()->info(sprintf('Cleared %s routing cache', $this->getManagedApp()));
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
    
    if (isset($config['routing']['param']['cache']))
    {    
      $class = $config['routing']['param']['cache']['class'];
      $parameters = $config['routing']['param']['cache']['param'];
      
      if ('sfMemcacheCache' == $class) $parameters['storeCacheInfo'] = true;
      
      try 
      {
        $cache = new $class($parameters);
        $cache->remove('symfony.routing.data'); // just remove the routing data
      }
      catch (Exception $e) { }
    }
  }

  /**
   * Clear cross app link cache
   */
  public function clearCrossAppCache() 
  {
    try 
    {
      $this->getCache()->removePattern('ca.*');
      
      if (sfConfig::get('sf_logging_enabled')) 
      {
        sfContext::getInstance()->getLogger()->info(sprintf('Cleared %s cross app cache', $this->getManagedApp()));
      }
    }
    catch (Exception $e) { }
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
    $site         = $this->getCurrentSite();
    
    if (false === $site) return; // no routes to register yet as dimension not set
    
    $routingProxy = $this->getRoutingProxy($router);
    $sitetrees    = SitetreeTable::getInstance()->getSitetreeNodes($site, Doctrine_Core::HYDRATE_RECORD, false);
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
        Sitetree::addToRouting($sitetree, $routingProxy, $junkChar, $urlStack);
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
   * Get the character to use to separate sitetree and listing item titles
   * 
   * @return string
   */
  public function getTitleSeparator()
  {
    return sfConfig::get('app_site_listing_title_separator', '-');
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
    $cache    = $this->getCache();
    $cacheUrl = str_replace(array('+', '?', '=', '@'), '', $url);
    $site     = $this->getCurrentSite();
    
    $cacheKey = "ca.$site.$app.$env.$cacheUrl";
    
    if ($cache->has($cacheKey)) 
    {
      return $cache->get($cacheKey);
    }

    if (!isset(self::$otherContexts[$app][$env])) 
    {
      // get config/context for our other app.  This will switch the current
      // context and change the contents of sfConfig, so we will need to change back after
      $otherConfiguration = ProjectConfiguration::getApplicationConfiguration($app, $env, $debug);
      self::$otherContexts[$app][$env] = sfContext::createInstance($otherConfiguration, $app . $env);
    } 
    else 
    {
      // we already initialised the other context, switch to it now
      sfContext::switchTo($app . $env);
    }
    
    try 
    {
      // make the url
      $generatedUrl = self::$otherContexts[$app][$env]->getController()->genUrl($url, true);
    } 
    catch (sfConfigurationException $e) 
    {
      $generatedUrl = '';
    }
    
    // switch back to old config
    sfContext::switchTo($currentApp);
    
    // to deal with different domains and controllers
    $generatedUrl = $this->processCrossAppUrl($generatedUrl, $currentApp, $app, $env);

    // save
    $cache->set($cacheKey, $generatedUrl);

    return $generatedUrl;
  }
  
  /**
   * Return the contents of $_SERVER or $_ENV for use when sfWebRequest isn't available
   * 
   * @return array()
   */
  public function getRequestPathInfoArray()
  {
    // trying to replicate default factories behaviour
    $pathVar = sfConfig::get('app_site_path_info_array', 'SERVER');
     
    if ('SERVER' == $pathVar) $pathInfoArray = $_SERVER;
    else $pathInfoArray = $_ENV;
    
    return $pathInfoArray;
  }
  
  /**
   * Returns the current host name, for use when sfWebRequest isn't available
   * 
   * @return string
   */
  public function getRequestHost()
  {
    $pathInfoArray = $this->getRequestPathInfoArray();
    
    if (isset($pathInfoArray['HTTP_X_FORWARDED_HOST']))
    {
      $elements = explode(',', $pathInfoArray['HTTP_X_FORWARDED_HOST']);
      $path = trim($elements[count($elements) - 1]);
    }
    else
    {
      $path =  isset($pathInfoArray['HTTP_HOST']) ? $pathInfoArray['HTTP_HOST'] : '';
    }
          
    return $path;
  }
  
  /**
   * Dynamically create the url for the managed app
   * 
   * @return string
   */
  public function getManagedAppUrl()
  {
    $request      = sfContext::getInstance()->getRequest();
    $siteConfig   = $this->getSite();
        
    // If single site with url_prefix different to CMS
    // Or multi-site set up
    if (isset($siteConfig['url_prefix']) && !empty($siteConfig['url_prefix']))
    {
      $hostName = $siteConfig['url_prefix'];
      
      if (false === strpos($hostName, 'http'))
      {
        
        $hostName = sprintf('http%s://%s', ($request->isSecure() ? 's' : ''), $hostName);
      } 
    }
    // Else it's the same as the backend
    else 
    {
      $hostName = sprintf('http%s://%s', ($request->isSecure() ? 's' : ''), $request->getHost());
    }
    
    return $hostName;
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
      // If the url returned doesn't already contain http(s)://
      if (false !== strpos($generatedUrl, 'http')) 
      {
        // Replace the backend domain with the frontend domain - note this will be the same if not set in config
        $request      = sfContext::getInstance()->getRequest();
        
        $hostName     = $this->getManagedAppUrl();
        $cmsHostName  = sprintf('http%s://%s', ($request->isSecure() ? 's' : ''), $request->getHost());
        $generatedUrl = str_replace($cmsHostName, $hostName, $generatedUrl);
        
        // Replace the controller in the URL
        if (false != strpos($generatedUrl, $fromApplication.'.php/') || false != strpos($generatedUrl, $fromApplication.'_'.$environment.'.php/'))
        {
          $fromController = ('prod' == $environment) ? $fromApplication.'.php' : $fromApplication.'_'.$environment.'.php';
          $toController   = ('prod' == $environment) ? '' : $toApplication.'_'.$environment.'.php';
          $generatedUrl   = str_replace($fromController, $toController, $generatedUrl);
        }
        
        return $generatedUrl;
      }
      else 
      {
        // Generate the domain from config / request
        $hostName = $this->getUrlForManagedApp();
        
        return $hostName . $generatedUrl;
      }
    }
    else 
    {
      throw new sfException("Unmanaged app: " . $toApplication);
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

    $context  = sfContext::getInstance();
    $response = $context->getResponse();
    $culture  = $context->getUser()->getCulture();

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
    
    $context->getRequest()->setAttribute('sitetree', $sitetree);

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
        SitetreeTable::getInstance()->setTreeQueryWithTranslation();
        $this->currentSitetreeAncestors = $currentSitetree->getNode()->getAncestors();
        SitetreeTable::getInstance()->resetTreeQuery();
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
  public function getEntireSitetree($site, $createRootIfNotExist = true, $includeTranslations = true, $hydrationMode = Doctrine_Core::HYDRATE_RECORD) 
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
   * @param string $site
   * @param int $level
   * @param boolean $justActive
   * @param array $excludeRange - optional left right range to exclude
   * @return array
   */
  public function getSitetreeForForm($site = null, $level = null, $justActive = true, $excludeRange = array(), $field = 'route_name') 
  {
    if ($site === null) 
    {
      $site = $this->getCurrentSite();
    }
    
    $tree = SitetreeTable::getInstance()->getSitetree($site, $level, Doctrine_Core::HYDRATE_ARRAY, $justActive);
    $culture = sfContext::getInstance()->getUser()->getCulture();

    foreach ($tree as $item) 
    {
      // This bit basically excludes a selected node + children from the list
      if (!empty($excludeRange) && $item['lft'] >= $excludeRange['lft'] && $item['rgt'] <= $excludeRange['rgt'])
      {
        $include = false;
      }
      else $include = true;
      
      if ($include) $out[$item[$field]] = str_repeat(':: ', $item['level']) . @$item['Translation'][$culture]['title'];
    }
    
    return $out;
  }
  
  /**
   * Get sitetree nodes marked as core navigation - cached so that user can configure but not high load on site
   */
  public function getCoreNavigation($site = null)
  {
    if ($site === null) 
    {
      $site = $this->getCurrentSite();
    }
    
    if (null === $this->coreNavigation) 
    {
      $this->coreNavigation = array();
      $loadedFromCache = false;
      
      if (sfConfig::get('sf_cache'))
      {
        $cache = $this->getCache();
        
        if ($cache->has('ca.core_navigation.'.$site)) 
        {
          $rawCoreNavigation = unserialize($cache->get('ca.core_navigation.'.$site));
          
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
      }
      
      if (!$loadedFromCache || empty($this->coreNavigation)) 
      {
        $rawCoreNavigation = SitetreeTable::getInstance()->getCoreNavigation($site);
        $cachedCoreNavigation = array();
        
        foreach ($rawCoreNavigation as $sitetree) 
        {
          $cachedCoreNavigation[] = $sitetree->toArray();
          $this->coreNavigation[] = $sitetree;
        }
        
        if (sfConfig::get('sf_cache')) $cache->set('ca.core_navigation.'.$site, serialize($cachedCoreNavigation), 86400);
      }
    }
    
    return $this->coreNavigation;
  }
  
  /**
   * Load an object of the specified class related to the specified sitetree
   *  e.g: the Page or Listing object associated with a sitetree - used when copying nodes
   *  
   * @param string $itemClass
   * @param Sitetree $sitetree
   */
  public function loadItemFromSitetree($itemClass, $sitetree) 
  {
    return Doctrine_Core::getTable($itemClass)->findOneBySitetreeId($sitetree->id);
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