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
			$category = ListingCategoryTable::getInstance()->findOneByIdentifier($request->getParameter('category'));
		}
		else $category = null;

		// try and render
		$this->renderListing($listing, $category, true);
		$this->sitetree = $sitetree;
	}
	
	/**
	 * Preview the listing page
	 *
	 * @param sfRequest $request
	 */
	public function executePreview($request) 
	{
		$listing = ListingTable::getInstance()->findOneById($request->getParameter('id'));
		$this->forward404Unless($listing, "No listing could be found with id='{$request->getParameter('id')}'");
		$sitetree = SitetreeTable::getInstance()->findOneById($listing->sitetree_id, false);
		$this->forward404Unless($sitetree, 'No sitetree');

		// use this as our currently matched sitetree
		siteManager::getInstance()->setCurrentSitetreeNode($sitetree);

		// tell our fragments to try and render from the request
		siteManager::getInstance()->setRenderFromRequest(true);

		$this->setTemplate('index');
		$this->renderListing($listing, null, false);
		$this->sitetree = $sitetree;
	}
	
	
	/**
	 * Display a listing page item
	 */
	public function executeItem() 
	{
		// find where we are in the sitetree
		$sitetree = siteManager::getInstance()->initCurrentSitetreeNode();
		$this->forward404Unless($sitetree, 'No sitetree node matched the current request');

		// find the content listing page from sitetree node
		$listing = ListingTable::getInstance()->findOneBySitetreeId($sitetree->id);
		$this->forward404Unless($listing, "No listing could be found from sitetree with id='{$sitetree->getId()}'");

		// load the item from the slug
		$type = $listing->type;
		$manager = listingManager::getInstance();
		$itemClass = $manager->getListItemClass($type);
		$slug = $this->getRequestParameter('slug', '');
		$item = Doctrine::getTable($itemClass)->findByDql("slug = ? AND listing_id = ? AND is_active = ?", array($slug, $listing->id, true))->getFirst();
		$this->forward404Unless($item, 'Could not locate item from slug: ' . $slug);

		$htmlTitle = $this->getResponse()->getTitle();
		$this->getResponse()->setTitle(htmlentities($htmlTitle . ' - ' . $item->title, null, 'utf-8', false), false);

		// try and render
		$this->renderItem($listing, $item, true);
		$this->sitetree = $sitetree;
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
	public function executePreviewItem($request) 
	{
		$listing = ListingTable::getInstance()->findOneById($request->getParameter('listId'));
		$this->forward404Unless($listing, "No listing could be found with id='{$request->getParameter('listId')}'");
		$sitetree = SitetreeTable::getInstance()->findOneById($listing->sitetree_id, false);
		$this->forward404Unless($sitetree, 'No sitetree');

		// use this as our currently matched sitetree
		siteManager::getInstance()->setCurrentSitetreeNode($sitetree);

		// load up the item
		$type = $listing->type;
		$itemClass = listingManager::getInstance()->getListItemClass($type);
		$item = ListingItemTable::getInstance()->findOneById($request->getParameter('itemId'));

		// tell our content blocks to try and render from the request
		siteManager::getInstance()->setRenderFromRequest(true);

		$this->setTemplate('item');
		$this->renderItem($listing, $item, false);
		$this->sitetree = $sitetree;
	}
	
  	/**
  	 * Display a listing page rss feed (rss 2.0)
  	 *
  	 * This looks up the page by site and routeName and renders it.
  	 */
  	public function executeRss($request) 
	{
	    // find where we are in the sitetree
	    $sitetree = siteManager::getInstance()->initCurrentSitetreeNode();
	    $this->forward404Unless($sitetree, 'No sitetree node matched the current request');
	
	    // find the content listing page from sitetree node
	    $listing = ListingTable::getInstance()->findOneBySitetreeId($sitetree->id);
	    $this->forward404Unless($listing, "No listing could be found from sitetree with id='{$sitetree->getId()}'");
	
	    // Check RSS enabled
	    $manager = ListingManager::getInstance();
	    $this->forward404Unless($manager->getRssEnabled($listing->type), 'RSS feed not enabled for the listing');
	    
	    // try and render
	    $feed = new sfRss201Feed();
	    $this->renderRss($feed, 'rss', $listing, $sitetree, $request, true);
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
  	public function executeAtom($request) 
	{
	    // find where we are in the sitetree
	    $sitetree = siteManager::getInstance()->initCurrentSitetreeNode();
	    $this->forward404Unless($sitetree, 'No sitetree node matched the current request');
	
	    // find the content listing page from sitetree node
	    $listing = ListingTable::getInstance()->findOneBySitetreeId($sitetree->id);
	    $this->forward404Unless($listing, "No listing could be found from sitetree with id='{$sitetree->getId()}'");
	
	    // Check RSS enabled
	    $manager = listingManager::getInstance();
	    $this->forward404Unless($manager->getRssEnabled($listing->type), 'Atom feed not enabled for the listing');
	    
	    // try and render
	    $feed = new sfAtom1Feed();
	    $this->renderRss($feed, 'atom', $listing, $sitetree, $request, true);
	    $this->sitetree = $sitetree;
	    
	    sfConfig::set('sf_web_debug', false);
	    $this->setLayout(false);
	    
	    // Set the headers
	    $feed->asXml();
	    
	    return sfView::SUCCESS;
	}
	
	/**
	 * Render the given listing
	 * 
	 * @param listing $listing
	 * @param listingCategory $category
	 * @param boolean $tryUseCache
	 */
	protected function renderListing($listing, $category = null, $tryUseCache = false) 
	{
		$manager = listingManager::getInstance();
	    $type = $listing->type;

	    // init content group
		$contentGroup = $this->initListingContentGroup($listing);

		// cache
		$useCache = false;
		
		if ($tryUseCache) 
		{
			// See if we should be using the cache for this template
			if ($manager->getTypeDefinitionParameter($type, 'listing_cacheable', false)) 
			{
				$useCache = true;
				$culture = sfContext::getInstance()->getUser()->getCulture();
				$page = $this->getRequestParameter('page', '');
				$categoryIdentifier = ($category ? $category->id : 'nc');
				$this->cacheName = "listing.{$listing->id}.listing.{$categoryIdentifier}.{$culture}{$page}";
			}
		}

		// Get a pager for the items
		$pager = $this->getInitialisedPager($manager, $listing, $category);

		// get template file location
		$templateFileLocation = $manager->getListingTemplateFile($type);
		$this->forward404Unless(file_exists($templateFileLocation), "Template file {$templateFileLocation} did not exist");

		// layout
		if ($customLayout = $manager->getTypeDefinitionParameter($type, 'listing_layout') && !$this->getRequest()->isXmlHttpRequest()) 
		{
		    $this->setLayout($customLayout);
		}

		$this->useCache = $useCache;
		$this->templateFileLocation = $templateFileLocation;
		$this->pager = $pager;
		$this->listing = $listing;
		$this->category = $category;
		$this->contentGroup = $contentGroup;
	}
	
	/**
	 * Render a listing item
	 *
	 * @param listing $listing
	 * @param listingItem $item
	 * @param boolean $tryUseCache
	 */
	protected function renderItem($listing, $item, $tryUseCache=false) 
	{
		$manager = listingManager::getInstance();
	    $type = $listing->type;

	    // init fragment group
		$this->initItemContentGroup($item);
		$this->initListingContentGroup($listing);

		// cache
		$useCache = false;
		if ($tryUseCache) 
		{
			// See if we should be using the cache for this template
			if ($manager->getTypeDefinitionParameter($type, 'item_cacheable', false)) 
			{
				$useCache = true;
				$culture = sfContext::getInstance()->getUser()->getCulture();
				$this->cacheName = "listing.{$listing->id}.{$item->id}.{$culture}";
			}
		}

		// layout
		if ($customLayout = $manager->getTypeDefinitionParameter($type, 'item_layout') && !$this->getRequest()->isXmlHttpRequest()) 
		{
		    $this->setLayout($customLayout);
		}

		// get template file location
		$templateFileLocation = $manager->getItemTemplateFile($type);
		$this->forward404Unless(file_exists($templateFileLocation), "Template file {$templateFileLocation} did not exist");

		$this->useCache = $useCache;
		$this->templateFileLocation = $templateFileLocation;
		$this->listing = $listing;
		$this->item = $item;
		$this->category = $item->ListingCategory;
	}
	
	/**
	 * Render an RSS feed
	 * 
	 * @param sfAtom1Feed or sfRss201Feed $feed
	 * @param string $feedType
	 * @param Listing $listing
	 * @param Sitetree $sitetree
	 * @param sfWebRequest $request
	 * @param boolean $tryUseCache
	 */
	protected function renderRss($feed, $feedType, $listing, $sitetree, $request, $tryUseCache=true) 
	{
	    $manager = listingManager::getInstance();
	    $siteConfig = siteManager::getInstance()->getSite();
	    $type = $listing->type;
	    $culture = sfContext::getInstance()->getUser()->getCulture();
	
	    // cache
	    $useCache = false;
	    
	    if ($tryUseCache) 
		{
	      	if (sfConfig::get('sf_cache', true)) $useCache = true;
	      	$cacheName = "listing.{$listing->id}.{$feedType}.{$culture}";
	    }
	    
	    $cache = siteManager::getInstance()->getCache();
	    
	    if ($useCache && $cache->has($cacheName)) 
		{
	      	$this->feedXml = $cache->get($cacheName);
	    }
	    else 
		{
	      	$contentGroup = $this->initListingContentGroup($listing);
	  
	      	// Set feed characteristics
	      	sfProjectConfiguration::getActive()->loadHelpers(array('Url','site'));
	      
	      	$feed->setTitle(xml_character_encode($sitetree->title));
	      	$feed->setLink(internal_url_for_sitetree($sitetree));
	      	$feed->setLanguage(str_replace('_','-',$culture));
	      	$feed->setFeedUrl(internal_url_for_sitetree($sitetree, $feedType));
	      
	     	$listingConfig = $manager->getTypeDefinitionParameter($type, 'rss_config');
	      
	      	if (isset($siteConfig['rss_config'])) 
			{
	        	// Feed image
	        	if (isset($siteConfig['rss_config']['logo']) || isset($siteConfig['rss_config']['favicon']))
				{
	          		$feedImage = new sfFeedImage();
	          
	          		if (isset($siteConfig['rss_config']['logo']) && is_array($siteConfig['rss_config']['logo'])) 
					{
	            		$icon = $siteConfig['rss_config']['logo'];
	            		$feedImage->setImage('http://'.$request->getHost().$icon['url'], array());
	            		$feedImage->setImageX($icon['width']);
	            		$feedImage->setImageY($icon['height']);
	            		$feedImage->setTitle(xml_character_encode($sitetree->title));
	            		$feedImage->setLink(internal_url_for_sitetree($sitetree));
	          		}
	          
	          		if (isset($siteConfig['rss_config']['favicon'])) 
					{
	           			$feedImage->setFavicon('http://'.$request->getHost().$siteConfig['rss_config']['favicon']);
	          		}
	          
	          		$feed->setImage($feedImage);
	        	}
	      	}
	        
	      	// Author
	      	if (isset($listingConfig['author'])) $author = $listingConfig['author'];
	      	else if (isset($siteConfig['rss_config']) && isset($siteConfig['rss_config']['author'])) $author = $siteConfig['rss_config']['author'];
	      
	      	if (isset($author)) 
			{
	        	$feed->setAuthorName(xml_character_encode($author['name']));
	        	$feed->setAuthorEmail($author['email']);
	        	$feed->setAuthorLink($author['link']);
	      	}
	      
	      	// Get a pager for the items
	      	$pager = $this->getInitialisedPager($manager, $listing, null, true);
	      
	      	// Create feed from config
	      	if (isset($listingConfig['feed'])) 
			{
	        	if (isset($listingConfig['feed']['description'])) 
				{
	          		$feed->setDescription(xml_character_encode(strip_tags($listing->renderContent($listingConfig['feed']['description']))));
	        	}
	      	}
	      
	      	$descriptionIdentifier = ((isset($listingConfig['item']) && isset($listingConfig['item']['description'])) ? $listingConfig['item']['description'] : false);
	      	$contentIdentifier = ((isset($listingConfig['item']) && isset($listingConfig['item']['content'])) ? $listingConfig['item']['content'] : false);
	      
	      	foreach ($pager->getResults() as $post) 
			{
	        	$post->ContentGroup->initialiseForRender($culture);
	        
	        	$item = new sfFeedItem();
	        	$item->setTitle(xml_character_encode($post->Translation[$culture]->get('title')));
	        	
	        	if ($post->ListingCategory) $item->setLink(internal_url_for_sitetree($sitetree, 'category_item', array('slug' => $post->slug, 'category' => $post->ListingCategory->slug)));
	        	else $item->setLink(internal_url_for_sitetree($sitetree, 'item', array('slug' => $post->slug)));
	        	
	        	$item->setPubdate(strtotime($post->get('created_at')));
	        	$item->setUniqueId($post->getSlug());
	        	if ($descriptionIdentifier && ($desc = $post->renderContent($descriptionIdentifier))) $item->setDescription(xml_character_encode(strip_tags($desc)));
	        	if ($contentIdentifier) $item->setContent($post->renderContent($contentIdentifier));
	        
	        	$feed->addItem($item);
	      	}
	      
	      	$feedXml = $feed->toXml();
	      
	      	if ($useCache) $cache->set($cacheName, $feedXml);
	      
	      	$this->feedXml = $feedXml;
	    }
  	}
	
	/**
	 * Initialise and return the ContentGroup for this listing
	 *
	 * @param listing_type $listing
	 * @return contentGroup
	 */
	protected function initListingContentGroup($listing) 
	{
	    $contentGroup = $listing->ContentGroup;
	    $contentGroup->initialiseForRender($this->getUser()->getCulture());

	    return $contentGroup;
	}
	
	/**
	 * Initialise and return the ContentGroup for this listing
	 *
	 * @param listingItem $item
	 * @return contentGroup
	 */
	protected function initItemContentGroup($item) 
	{
	    $contentGroup = $item->ContentGroup;
	    $contentGroup->initialiseForRender($this->getUser()->getCulture());

	    return $contentGroup;
	}
	
	/**
	 * Initialse and return our pager
	 *
	 * @param listingManager $manager
	 * @param Listing $listing
	 * @param ListingCategory $category
	 * @param boolean $isRss
	 * @return sfDoctrineSuperPager
	 */
	protected function getInitialisedPager($manager, $listing, $category, $isRss = false) 
	{
		$type = $listing->type;
		$pagerClass = $manager->getDisplayPagerClass($type);

		// make our pager
		$pager = new $pagerClass($listing);

		// do the page number
		$maxPerPage = $listing->results_per_page;
		$page = $this->getRequestParameter('page', 1);
		
		if ($page == 0) { // page==0 means view all
			$page = 1;
			$maxPerPage = 0;
		}
		
		$pager->setPage($page);

		// restrict to items from this content listing
		$this->addListItemRestrictions($pager, $listing, $category);

		// add in ordering clause
		$q = $pager->getQuery();
		
		if ($listing->use_custom_order) 
		{
			$q->orderBy('ordr');
		} 
		elseif ($isRss) 
		{
	      	$q->orderBy($manager->getRssItemOrdering($type));
	    }
	    else 
		{
			$q->orderBy($manager->getListItemOrdering($type));
		}
		
		$pager->setMaxPerPage($maxPerPage);

		// get the pager to add the filter form values
		$pager->addFilterValuesToQuery($this->getRequest());

		// do the pager query
		$pager->init();

		return $pager;
	}
	
	/**
	 * Restrict the pager to items from this content listing and only published items!
	 * 
	 * @param sfDoctrineSuperPager $pager
	 * @param listing $listing
	 * @param listingCategory $category
	 */
	protected function addListItemRestrictions($pager, $listing, $category) 
	{
		$pager->getQuery()->addWhere('listing_id = ? AND is_active = ? AND is_hidden = ?', array($listing->id, true, false));
		
		if ($category) 
		{
			$pager->getQuery()->addWhere('listing_category_id = ?', array($category->id));
		}
	}
}
