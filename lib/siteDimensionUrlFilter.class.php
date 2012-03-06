<?php
/**
 * siteDimensionUrlFilter - This sets the dimension of the site based on the url.
 * 
 * See the README for configuration setup.
 * 
 * @author Jo Carter <work@jocarter.co.uk>
 */
class siteDimensionUrlFilter extends sfFilter
{
  public function execute($filterChain)
  {
    // only execute once
    if ($this->isFirstCall())
    {
      $request = $this->getContext()->getRequest();

      // Get the dimension based on the URL
      $dimension = sfConfig::get('app_dimensions_' . $request->getHost());
      
      // If it's not been set - then use the default one
      if (!$dimension)
      {
        $dimension = siteManager::getInstance()->getDefaultSite();
      }
      
      sfProjectConfiguration::getActive()->setDimension(array('site' => $dimension));
      sfProjectConfiguration::getActive()->initConfiguration(); // re-init to load the dimension's config/app.yml
      
      if (sfConfig::get('sf_logging_enabled'))
      {
        sfContext::getInstance()->getLogger()->log('Setting dimension to : ' . $dimension);
      }
      
      // Set default culture
      sfContext::getInstance()->getUser()->setCulture(siteManager::getInstance()->getDefaultCulture());
      
      if (sfConfig::get('sf_logging_enabled'))
      {
        sfContext::getInstance()->getLogger()->log('Setting culture to : ' . siteManager::getInstance()->getDefaultCulture());
      }
      
      // Need to reload routes to get the current site's
      sfContext::getInstance()->getRouting()->clearRoutes();
      sfContext::getInstance()->getRouting()->loadConfiguration();
    }

    $filterChain->execute();
  }
}
?>