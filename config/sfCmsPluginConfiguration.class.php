<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfCmsPlugin configuration.
 * 
 * @package    sfCmsPlugin
 * @subpackage config
 * @author     Jo Carter <work@jocarter.co.uk>
 * @version    SVN: $Id: sfCmsPluginConfiguration.class.php 23319 2009-10-25 12:22:23Z Kris.Wallsmith $
 */
class sfCmsPluginConfiguration extends sfPluginConfiguration 
{
  /**
   * Register the sitetree routing manager listener
   */
  public function setup() 
  {
    $this->dispatcher->connect(
        'routing.load_configuration', 
        array('siteManager', 'routingLoadConfigurationListener')
      );
      
    // Register config handler for dimension app.yml files
    if ($this->configuration instanceof sfApplicationConfiguration)
    {
      $configCache = $this->configuration->getConfigCache();
      $configCache->registerConfigHandler('config/*/app.yml', 'sfDefineEnvironmentConfigHandler',
        array('prefix' => 'app_'));
    }
  }
  
  /**
   * When the plugin is initialized - set up the dimension as we have the config at this point
   */
  public function initialize()
  {
    // Don't do this if it's the command line
    if ('cli' != php_sapi_name())
    {
      // We are dealing with a multi-site app.
      if (sfConfig::get('sf_app') == siteManager::getInstance()->getManagedApp() 
                  && class_exists('ysfApplicationConfiguration') 
                  && $this->configuration instanceof ysfApplicationConfiguration)
      {
        // If dimension not set set and reload config
        if (is_null($this->configuration->getDimension())) 
        {
          // Check where we are
          $path      = siteManager::getInstance()->getRequestHost();
          $dimension = sfConfig::get('app_dimensions_'.$path, siteManager::getInstance()->getDefaultSite());
          
          // Set the dimension
          $this->configuration->setDimension(array('site' => $dimension));
          
          // Load the config
          siteManager::getInstance()->loadSiteConfig($dimension);
        }
      }
      else 
      {
        // See if it's set in a cookie and load the config
        $app          = sfConfig::get('sf_app');
        $dimension   = (isset($_COOKIE['site_' . $app]) ? $_COOKIE['site_' . $app] : false);
        $activeSites = sfConfig::get('app_site_active_sites', array());
        if (!empty($activeSites)) $activeSites = array_keys($activeSites);
        
        if ($dimension && in_array($dimension, $activeSites))
        {
          // Load the config
          siteManager::getInstance()->loadSiteConfig($dimension);
        }
      }
    }
  }
}
  