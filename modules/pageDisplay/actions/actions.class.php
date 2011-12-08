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
		$sitetreeNode = $siteManager->initCurrentSitetreeNode();
		
		if (!$sitetreeNode) 
		{
			$this->forward404('No sitetree node matched the current request');
		}

		// find page from sitetree node
		$page = PageTable::getInstance()->findOneBySitetreeId($sitetreeNode->id);
		$this->forward404Unless($page, 'No page could be found for this sitetree');

		$this->render($page, true);
		$this->sitetree = $sitetreeNode;
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
		$sitetree = SitetreeTable::getInstance()->findOneById($page->sitetree_id, false);
		$this->forward404Unless($sitetree, 'No sitetree');

		$siteManager = siteManager::getInstance();
		$siteManager->setCurrentSitetreeNode($sitetree);

		// tell our content blocks to try and render from the request
		siteManager::getInstance()->setRenderFromRequest(true);

		$this->setTemplate('index');
		$this->render($page, false);
		$this->sitetree = $sitetree;
	}
	
	/**
	 * Actually render a page
	 *
	 * @param page $page
	 * @param boolean $tryUseCache
	 */
	protected function render($page, $tryUseCache = false) 
	{	
		$contentManager = pageManager::getInstance();
		$templateSlug = $page->template;

		// Initialise content group
		$contentGroup = $this->initContentGroup($page);

		// cache
		$useCache = false;
		
		if ($tryUseCache) 
		{
			// See if we should be using the cache for this template
			if ($contentManager->getTemplateDefinitionAttribute($templateSlug, 'cacheable', false)) 
			{
				$useCache = true;
				$culture = sfContext::getInstance()->getUser()->getCulture();
				$this->cacheName = "contentGroup.{$contentGroup->id}.{$culture}";
			}
		}
		$this->useCache = $useCache;

		// template location
		$this->templateFileLocation = $contentManager->getTemplateFileLocation($templateSlug);

		// layout
		if (($customLayout = $contentManager->getTemplateDefinitionAttribute($templateSlug, 'layout')) && !$this->getRequest()->isXmlHttpRequest()) 
		{
		    $this->setLayout($customLayout);
		}

		$this->page = $page;
		$this->contentGroup = $contentGroup;
	}
	
	/**
	 * @param page $page
	 * @return contentGroup
	 */
	protected function initContentGroup($page) 
	{
	    $contentGroup = $page->ContentGroup;
	    $contentGroup->initialiseForRender($this->getUser()->getCulture());
	    return $contentGroup;
	}
}
