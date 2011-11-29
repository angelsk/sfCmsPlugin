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
}
  