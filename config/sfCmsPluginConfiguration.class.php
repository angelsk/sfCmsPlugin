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
  }
  
  /**
   * When the plugin is initialized - set up the dimension as we have the config at this point
   */
  public function initialize()
  {
    $config = ProjectConfiguration::getActive();
  
    if (sfConfig::get('sf_app') == siteManager::getInstance()->getManagedApp())
    {
      if (class_exists('ysfApplicationConfiguration') && $config instanceof ysfApplicationConfiguration)
      {
        // We are dealing with a multi-site app.
        if ($config instanceof ysfApplicationConfiguration)
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
          
          $config->setDimension(array('site' => $dimension));
        }
      }
    }
  }
}
  