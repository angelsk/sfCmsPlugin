<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package    symfony
 * @subpackage i18n
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfI18nApplicationExtract.class.php 14872 2009-01-19 08:32:06Z fabien $
 */
class cmsI18nApplicationExtract extends sfI18nApplicationExtract
{
  protected $extractObjects = array();

  /**
   * Configures the current extract object.
   */
  public function configure()
  {
    $this->extractObjects = array();

    // Modules
    $moduleNames = sfFinder::type('dir')->maxdepth(0)->relative()->in(sfConfig::get('sf_app_module_dir'));
    foreach ($moduleNames as $moduleName)
    {
      $this->extractObjects[] = new sfI18nModuleExtract($this->i18n, $this->culture, array('module' => $moduleName));
    }
  }

  /**
   * Extracts i18n strings.
   *
   * This class must be implemented by subclasses.
   */
  public function extract()
  {
    foreach ($this->extractObjects as $extractObject)
    {
      $extractObject->extract();
    }

    // Add global templates
    $this->extractFromPhpFiles(sfConfig::get('sf_app_template_dir'));

    // ADDED: lib/form,validator and /templates
    $this->extractFromPhpFiles(sfConfig::get('sf_root_dir').DIRECTORY_SEPARATOR.'templates');
    $this->extractFromPhpFiles(sfConfig::get('sf_lib_dir').DIRECTORY_SEPARATOR.'form');
    $this->extractFromPhpFiles(sfConfig::get('sf_lib_dir').DIRECTORY_SEPARATOR.'validator');
    $this->extractFromPhpFiles(sfConfig::get('sf_lib_dir').DIRECTORY_SEPARATOR.'helper');
    
    // Add global librairies
    $this->extractFromPhpFiles(sfConfig::get('sf_app_lib_dir'));
  }
}
