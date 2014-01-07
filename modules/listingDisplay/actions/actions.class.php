<?php

/**
 * listingDisplay actions.
 *
 * @package    site_cms
 * @subpackage listingDisplay
 * @author     Jo Carter
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class listingDisplayActions extends sfActions
{
  /**
   * Display a listing page
   *
   * This looks up the page by routeName and renders it.
   */
  public function executeIndex(sfWebRequest $request)
  {
    // find where we are in the sitetree
    $sitetree = siteManager::getInstance()->initCurrentSitetreeNode();
    $this->forward404Unless($sitetree, 'No sitetree node matched the current request');

    // find the content listing page from sitetree node
    $listing = ListingTable::getInstance()->findOneBySitetreeId($sitetree->id);
    $this->forward404Unless($listing, "No listing could be found from sitetree with id='{$sitetree->getId()}'");

    if ($request->hasParameter('category'))
    {
      $category = ListingCategoryTable::getInstance()->findOneBySlug($request->getParameter('category'));
      $this->forward404Unless($category, "No category could be found for the listing with slug='{$request->getParameter('category')}'");
    }
    
    // layout
    $manager = listingManager::getInstance();
    
    if ($customLayout = $manager->getTemplateDefinitionParameter($listing->template, 'listing_layout') && !$this->getRequest()->isXmlHttpRequest())
    {
      $this->setLayout($customLayout);
    }

    // try and render
    $this->setVar('content', $listing->render(true, $request, $sitetree), true);
  }

  /**
   * Preview the listing page
   *
   * @param sfRequest $request
   */
  public function executePreview(sfWebRequest $request)
  {
    $listing = ListingTable::getInstance()->findOneById($request->getParameter('id'));
    $this->forward404Unless($listing, "No listing could be found with id='{$request->getParameter('id')}'");
    $sitetree = SitetreeTable::getInstance()->findOneById($listing->sitetree_id);
    $this->forward404Unless($sitetree, 'No sitetree');

    // use this as our currently matched sitetree
    siteManager::getInstance()->setCurrentSitetreeNode($sitetree);
    $sitetree = siteManager::getInstance()->initCurrentSitetreeNode(); // set the meta-data - why not :)

    // tell our fragments to try and render from the request
    siteManager::getInstance()->setRenderFromRequest(true);
    
    // layout
    $manager = listingManager::getInstance();
    
    if ($customLayout = $manager->getTemplateDefinitionParameter($listing->template, 'listing_layout') && !$this->getRequest()->isXmlHttpRequest())
    {
      $this->setLayout($customLayout);
    }

    // try and render
    $this->setTemplate('index');
    $this->setVar('content', $listing->render(false, $request, $sitetree), true);
  }


  /**
   * Display a listing page item
   */
  public function executeItem(sfWebRequest $request)
  {
    // find where we are in the sitetree
    $sitetree = siteManager::getInstance()->initCurrentSitetreeNode();
    $this->forward404Unless($sitetree, 'No sitetree node matched the current request');

    // find the content listing page from sitetree node
    $listing = ListingTable::getInstance()->findOneBySitetreeId($sitetree->id);
    $this->forward404Unless($listing, "No listing could be found from sitetree with id='{$sitetree->getId()}'");

    // load the item from the slug
    $template = $listing->template;
    $manager = listingManager::getInstance();
    $itemClass = $manager->getListItemClass($template);
    $slug = $this->getRequestParameter('slug', '');
    $item = Doctrine_Core::getTable($itemClass)->findByDql("slug = ? AND listing_id = ? AND is_active = ?", array($slug, $listing->id, true))->getFirst();
    $this->forward404Unless($item, 'Could not locate item from slug: ' . $slug);

    // check in correct category if supplied
    if ($request->hasParameter('category'))
    {
      $category  = $request->getParameter('category');
      $actualCat = $item->ListingCategory->slug;
     
      if ($category != $actualCat) $this->redirect(siteManager::getInstance()->getRoutingProxy()->generateInternalUrl($sitetree, 'category_item', array('sf_culture'=>$this->getUser()->getCulture(), 'slug'=>$item->slug, 'category'=>$actualCat)), 301);
    }
    
    $htmlTitle = $this->getResponse()->getTitle();
    $htmlTitle = sprintf('%s %s %s', $item->title, siteManager::getInstance()->getTitleSeparator(), $htmlTitle);
    $this->getResponse()->setTitle(htmlentities($htmlTitle, null, 'utf-8', false), false);
    
    // layout
    $manager = listingManager::getInstance();
    
    if ($customLayout = $manager->getTemplateDefinitionParameter($template, 'item_layout') && !$this->getRequest()->isXmlHttpRequest())
    {
      $this->setLayout($customLayout);
    }
    
    // try and render
    $this->setVar('content', $item->render(true, $sitetree, $listing), true);
  }

  /**
   * Preview a listing item.
   *
   * Expects:
   *
   * listId - the listing
   * itemId - the id of the listingItem (or other item class)
   *
   * @param sfWebRequest $request
   */
  public function executePreviewItem(sfWebRequest $request)
  {
    $listing = ListingTable::getInstance()->findOneById($request->getParameter('listId'));
    $this->forward404Unless($listing, "No listing could be found with id='{$request->getParameter('listId')}'");
    $sitetree = SitetreeTable::getInstance()->findOneById($listing->sitetree_id);
    $this->forward404Unless($sitetree, 'No sitetree');

    // use this as our currently matched sitetree
    siteManager::getInstance()->setCurrentSitetreeNode($sitetree);
    $sitetree = siteManager::getInstance()->initCurrentSitetreeNode(); // set the meta-data - why not :)

    // load up the item
    $template = $listing->template;
    $itemClass = listingManager::getInstance()->getListItemClass($template);
    $item = ListingItemTable::getInstance()->findOneById($request->getParameter('itemId'));

    // tell our content blocks to try and render from the request
    siteManager::getInstance()->setRenderFromRequest(true);

    // layout
    $manager = listingManager::getInstance();
    
    if ($customLayout = $manager->getTemplateDefinitionParameter($template, 'item_layout') && !$this->getRequest()->isXmlHttpRequest())
    {
      $this->setLayout($customLayout);
    }
    
    $this->setTemplate('item');
    // try and render
    $this->setVar('content', $item->render(false, $sitetree, $listing), true);
  }

  /**
   * Display a listing page rss feed (rss 2.0)
   *
   * This looks up the page by site and routeName and renders it.
   */
  public function executeRss(sfWebRequest $request)
  {
    // find where we are in the sitetree
    $sitetree = siteManager::getInstance()->initCurrentSitetreeNode();
    $this->forward404Unless($sitetree, 'No sitetree node matched the current request');

    // find the content listing page from sitetree node
    $listing = ListingTable::getInstance()->findOneBySitetreeId($sitetree->id);
    $this->forward404Unless($listing, "No listing could be found from sitetree with id='{$sitetree->getId()}'");

    // Check RSS enabled
    $manager = ListingManager::getInstance();
    $this->forward404Unless($manager->getRssEnabled($listing->template), 'RSS feed not enabled for the listing');

    // try and render
    $feed = new sfRss201Feed();
    $this->setVar('feedXml', $listing->renderRss($feed, 'rss', $sitetree, $request, true), true);
    $this->sitetree = $sitetree;

    sfConfig::set('sf_web_debug', false);
    $this->setLayout(false);

    // Set the headers
    $feed->asXml();

    return sfView::SUCCESS;
  }


  /**
   * Display a listing page rss feed (atom)
   *
   * This looks up the page by site and routeName and renders it.
   */
  public function executeAtom(sfWebRequest $request)
  {
    // find where we are in the sitetree
    $sitetree = siteManager::getInstance()->initCurrentSitetreeNode();
    $this->forward404Unless($sitetree, 'No sitetree node matched the current request');

    // find the content listing page from sitetree node
    $listing = ListingTable::getInstance()->findOneBySitetreeId($sitetree->id);
    $this->forward404Unless($listing, "No listing could be found from sitetree with id='{$sitetree->getId()}'");

    // Check RSS enabled
    $manager = listingManager::getInstance();
    $this->forward404Unless($manager->getRssEnabled($listing->template), 'Atom feed not enabled for the listing');

    // try and render
    $feed = new sfAtom1Feed();
    $this->setVar('feedXml', $listing->renderRss($feed, 'atom', $sitetree, $request, true), true);
    $this->sitetree = $sitetree;

    sfConfig::set('sf_web_debug', false);
    $this->setLayout(false);

    // Set the headers
    $feed->asXml();

    return sfView::SUCCESS;
  }
}
