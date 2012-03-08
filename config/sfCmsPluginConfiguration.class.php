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
          // trying to replicate default factories behaviour as sfWebRequest not accessible here
          $pathVar = sfConfig::get('app_site_path_info_array', 'SERVER');
           
          if ('SERVER' == $pathVar) $pathInfoArray = $_SERVER;
          else $pathInfoArray = $_ENV;
          
          if (isset($pathInfoArray['HTTP_X_FORWARDED_HOST']))
          {
            $elements = explode(',', $pathInfoArray['HTTP_X_FORWARDED_HOST']);
            $path = trim($elements[count($elements) - 1]);
          }
          else
          {
            $path =  isset($pathInfoArray['HTTP_HOST']) ? $pathInfoArray['HTTP_HOST'] : '';
          }
          
          $dimension = sfConfig::get('app_dimensions_'.$path, siteManager::getInstance()->getDefaultSite());
          
          $this->configuration->setDimension(array('site' => $dimension));
          
          // Load dimensions config (if it exists) - requires the config handler registered above
          if (file_exists(sprintf('%s/config/%s/app.yml', sfConfig::get('sf_root_dir'), $dimension)))
          {
            if ($file = $this->configuration->getConfigCache()->checkConfig(sprintf('config/%s/app.yml', $dimension), true))
            {
              include($file);
            }
          }
        }
      }
    }
  }
}
  