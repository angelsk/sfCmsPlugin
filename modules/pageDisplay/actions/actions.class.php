<?php

/**
 * pageDisplay actions.
 *
 * @package    site_cms
 * @subpackage pageDisplay
 * @author     Jo Carter
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class pageDisplayActions extends sfActions
{
  /**
   * Display a page
   *
   * This looks up the current sitetree node and renders the page linked to it.
   */
  public function executeIndex()
  {
    // find sitetree node from route matched
    $siteManager = siteManager::getInstance();
    $contentManager = pageManager::getInstance();
    $sitetreeNode = $siteManager->initCurrentSitetreeNode();

    if (!$sitetreeNode)
    {
      $this->forward404('No sitetree node matched the current request');
    }

    // find page from sitetree node
    $page = PageTable::getInstance()->findOneBySitetreeId($sitetreeNode->id);
    $this->forward404Unless($page, 'No page could be found for this sitetree');

    // layout
    if (($customLayout = $contentManager->getTemplateDefinitionAttribute($page->template, 'layout')) && !$this->getRequest()->isXmlHttpRequest())
    {
      $this->setLayout($customLayout);
    }
    
    $this->setVar('content', $page->render(true, $sitetreeNode), true);
  }

  /**
   * Preview a page
   *
   * This previews a particular page with content from the POST request.  It
   * is called from the backend editing component in an iframe.
   *
   * Can also be accessed directly - and will load the currently live versions then
   * (useful for unpublished pages)
   *
   * @param sfRequest $request
   */
  public function executePreview($request)
  {
    $page = $page = PageTable::getInstance()->findOneById($request->getParameter('id'));
    $this->forward404Unless($page, 'No such page');

    $sitetree = SitetreeTable::getInstance()->findOneById($page->sitetree_id);
    $this->forward404Unless($sitetree, 'No sitetree');

    $siteManager = siteManager::getInstance();
    $contentManager = pageManager::getInstance();
    $siteManager->setCurrentSitetreeNode($sitetree);
    $sitetree = siteManager::getInstance()->initCurrentSitetreeNode(); // set the meta-data - why not :)

    // tell our content blocks to try and render from the request
    siteManager::getInstance()->setRenderFromRequest(true);

    // layout
    if (($customLayout = $contentManager->getTemplateDefinitionAttribute($page->template, 'layout')) && !$this->getRequest()->isXmlHttpRequest())
    {
      $this->setLayout($customLayout);
    }
    
    $this->setTemplate('index');
    $this->setVar('content', $page->render(false, $sitetree), true);
  }

}
