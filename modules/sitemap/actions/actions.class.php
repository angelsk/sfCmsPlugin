<?php

/**
 * sitemap actions.
 *
 * @package    sfCmsPlugin
 * @subpackage sitemap
 * @author     Jo Carter <work@jocarter.co.uk>
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class sitemapActions extends sfActions 
{
  public function executeIndex(sfWebRequest $request) 
  {
    $manager = siteManager::getInstance();
    $sitetreeNode = $manager->initCurrentSitetreeNode();
    
    $this->entireSitetree = $manager->getEntireSitetree($sitetreeNode->site);
    $this->sitetree = $sitetreeNode;
  }
  
  public function executeSitemap(sfWebRequest $request) 
  {
    $manager = siteManager::getInstance();
    $sitetreeNode = $manager->initCurrentSitetreeNode();
    
    $this->entireSitetree = $manager->getEntireSitetree($sitetreeNode->site);
    
    $this->getResponse()->clearHttpHeaders();
    $this->getResponse()->setHttpHeader('Content-Type','text/xml; charset=utf8');
    $this->setLayout(false);
  }
}
