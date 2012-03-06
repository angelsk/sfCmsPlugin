<?php
/**
 * siteDimensionFilter  This sets the dimension of the site based on the user session
 * Used for the backend
 * 
 * See the README for configuration setup.
 * 
 * @author Jo Carter
 */
class siteDimensionFilter extends sfFilter
{
  public function execute($filterChain)
  {
    // only execute once
    if ($this->isFirstCall())
    {
      // Set the dimension from somewhere into the user attribute - $this->getUser()->setAttribute('site', $selectedDimension, 'dimension');
      $site = sfContext::getInstance()->getUser()->getAttribute('site', siteManager::getInstance()->getDefaultSite(), 'dimension');
      
      ProjectConfiguration::getActive()->setDimension(array('site'=>$site));
    }
    
    $filterChain->execute();
  }
}
  