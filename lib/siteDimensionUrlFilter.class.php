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
    }

    $filterChain->execute();
  }
}
?>