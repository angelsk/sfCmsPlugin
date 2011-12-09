<?php

/**
 * page admin actions.
 *
 * @package    site_cms
 * @subpackage pageAdmin
 * @author     Jo Carter
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class pageAdminActions extends sfActions 
{
	/**
	 * Edit a page by routeName
	 *
	 * If the page does not exist it asks the user for a template,
	 * creates the page and redirects to the usual editing interface.  If the
	 * page does exist, it just redirects the user to the editing page.
	 */
	public function executeEditByRoute(sfWebRequest $request) 
	{
		$this->forward404Unless($this->hasRequestParameter('routeName'));

		$routeName = $this->getRequestParameter('routeName');

		$sitetree = SitetreeTable::getInstance()->retrieveByRoutename($routeName);
		$page = PageTable::getInstance()->findOneBySitetreeId($sitetree->id);
		
		if ($page) 
		{
			// a page has already been created for this sitetree
			$this->redirect("pageAdmin/edit?id=$page->id");
		}
   
  		// We must create a new page object, unsaved
    	$page = page::createFromSitetree($sitetree);
		$form = new pageForm($page);
		
		if ($request->hasMethod(sfWebRequest::POST)) 
		{
			$form->bind($request->getParameter('page'));
		    
			if ($form->isValid()) 
			{
				// user has selected a template, create the page and content group etc
				$form->save();
				$page = $form->getObject();
				$page->updateNew();
				
				$this->redirect("pageAdmin/edit?id=$page->id");
		  	}
		}

		$this->routeName = $routeName;
		$this->sitetree = $sitetree;
		$this->form = $form;
	}
	
	/**
	 * Edit a page
	 *
	 * This doesn't do a lot itself, it just calls the content block editing
	 * component to do all the hard work.
	 */
	public function executeEdit(sfWebRequest $request) 
	{
		$this->forward404Unless($this->hasRequestParameter('id'));
		
		$pageId = $request->getParameter('id');
		$page = PageTable::getInstance()->findOneById($pageId);
		
		$this->forward404Unless($page);

    	$sitetree = SitetreeTable::getInstance()->findOneById($page->sitetree_id);

		// check that the template is allowed
		$contentManager = pageManager::getInstance();
		$possibleTemplates = $contentManager->getPossibleTemplatesForPage($page);
		
		if (!isset($possibleTemplates[$page->template])) 
		{
		    $this->redirect("pageAdmin/editTemplate?id=$page->id");
		}

		$contentGroup = $page->ContentGroup;
		$contentGroup->setCurrentLang($this->getUser()->getCulture());

		$this->page = $page;
		$this->sitetree = $sitetree;
		$this->contentGroup = $contentGroup;
	}
	
	/**
	 * Edit a page's template (not versioned)
	 *
	 * Changing the template can be a destructive action, as it deletes 
	 * content blocks which do not exist in the new template.
	 */
	public function executeEditTemplate($request) 
	{
		$this->forward404Unless($this->hasRequestParameter('id'));
		
		$pageId = $request->getParameter('id');
		$page = PageTable::getInstance()->findOneById($pageId);
		
		$this->forward404Unless($page);
		
		$sitetree = SitetreeTable::getInstance()->findOneById($page->sitetree_id);

		$form = new pageForm($page);

    	if ($request->hasMethod(sfWebRequest::POST)) 
		{
    		$form->bind($request->getParameter('page'));
		    
			if ($form->isValid()) 
			{
				$form->save();
				$page = $form->getObject();
				
				$this->redirect("pageAdmin/edit?id=$page->id");
      		}
		}

		$this->page = $page;
		$this->sitetree = $sitetree;
		$this->form = $form;
	}
}
