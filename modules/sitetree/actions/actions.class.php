<?php

/**
 * sitetree actions.
 *
 * @package    sfCmsPlugin
 * @subpackage sitetree
 * @author     Jo Carter <work@jocarter.co.uk>
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class sitetreeActions extends sfActions
{
  public function preExecute()
  {
    $this->getResponse()->addJavascript('/sfCmsPlugin/js/sitetree.js', 'last');
    
    // Permissions
    $user = sfContext::getInstance()->getUser();
    $this->canAdmin   = $user->hasCredential('site.admin');
    $this->canPublish = ($this->canAdmin || $user->hasCredential('site.publish'));
  }
  
  /**
   * Change the language in the CMS
   */
  public function executeChangeLanguage(sfWebRequest $request)
  {
    $this->getUser()->setCulture($this->getRequestParameter('sf_culture'));
    $url = ($this->getRequest()->getReferer() != '' ? $this->getRequest()->getReferer() : '@homepage');
    $this->redirect($url);
  }
  
  /**
   * Change the site in the CMS
   * 
   * @param sfWebRequest $request
   */
  public function executeChangeSite(sfWebRequest $request)
  {
    $this->sites = siteManager::getInstance()->getActiveSites();
    $selectedSite = false;
    
    if (empty($this->sites)) $this->redirect('sitetree/index'); // will use default site
    
    $shortSites = array_keys($this->sites);
    
    if (1 == count($this->sites))       $selectedSite = $shortSites[0]; // If only one site
    if ($request->hasParameter('site')) $selectedSite = $request->getParameter('site', false); // If already selected site

    if ($selectedSite && in_array($selectedSite, $shortSites))
    {
      siteManager::getInstance()->setCurrentSite($selectedSite);
      
      $this->redirect('sitetree/index');
    }
  }
  
  /**
   * Executes index action
   */
  public function executeIndex(sfWebRequest $request) 
  {
    $manager           = siteManager::getInstance();
    $site              = $manager->getCurrentSite();
    $this->treeNodes   = $manager->getEntireSitetree($site);
    $this->approvals   = SiteApprovalTable::getInstance()->getOrderedApprovalsForSite($site);
    
    if (1 == count($this->treeNodes))
    {
      // Get potential sites to copy from
      $this->sites = SitetreeTable::getInstance()->getSitesToCopyFrom();
    }
  }
  
  /**
   * Copy a sitetree from the requested site, to the new site
   * 
   * @param sfWebRequest $request
   */
  public function executeCopy(sfWebRequest $request)
  {
    $this->forward404Unless($this->canPublish, "Don't have permission to import sitetree");
    $this->forward404Unless(($copyFromSite = $request->getParameter('site')), 'No site selected');
    
    // Check current site has only root node
    $manager     = siteManager::getInstance();
    $site        = $manager->getCurrentSite();
    $treeNodes   = $manager->getEntireSitetree($site);
    
    $this->forward404Unless((1 == count($treeNodes)), 'Site already has structure');
    
    // Check copying site is in sitesToCopyFrom
    $sites = SitetreeTable::getInstance()->getSitesToCopyFrom();
    
    $this->forward404Unless(in_array($copyFromSite, $sites), 'Site not eligible to copy from');
    
    // Call $oldRoot->copyTo($newRoot);
    $currentRoot = $treeNodes->getFirst();
    $copyFromRoot = SitetreeTable::getInstance()->findOneBySiteAndRouteName($copyFromSite, 'homepage');
    
    try 
    {
      $copyFromRoot->copyTo($currentRoot);
      $this->getUser()->setFlash('notice', 'Sitetree structure copied');
    }
    catch (Exception $e)
    {
      $this->getUser()->setFlash('error', $e->getMessage());
    }
    
    // tell the manager the sitetree has changed so we can refresh the cache
    siteManager::getInstance()->sitetreeChanged();
    
    $this->redirect('sitetree/index');
  }
  
  /**
   * Edit a sitetree node
   */
  public function executeEdit(sfWebRequest $request) 
  {
    $sitetree = SitetreeTable::getInstance()->findOneById($request->getParameter('id'));
    $this->forward404Unless($sitetree);

    $form = new editSitetreeForm($sitetree);

    if (($request->isMethod(sfWebRequest::POST) || $request->isMethod(sfWebRequest::PUT)) && $request->hasParameter('sitetree')) 
    {
      // form was submitted
      $form->bind($request->getParameter('sitetree'));

      if ($form->isValid()) 
      {
        $form->save();
        
        // tell the manager the sitetree has changed so we can refresh the cache
        siteManager::getInstance()->sitetreeChanged();
        
        $this->getUser()->setFlash('notice', 'The changes to "' . $sitetree->getTitle() . '" have been saved');
        $this->redirect('sitetree/index');
      }
    }

    $this->form = $form;
  }
  
  /**
   * Create a new node under the given parent.
   */
  public function executeCreate(sfWebRequest $request) 
  {
    $parentId = $this->getRequestParameter('parent');
    $parent = SitetreeTable::getInstance()->findOneById($parentId);
    $this->forward404Unless($parent, 'A parent was not provided for creation');

    $form = new createSitetreeForm($parent);

    if ($request->isMethod(sfWebRequest::POST) && $request->hasParameter('sitetree')) 
    {
      // form was submitted
      $form->bind($request->getParameter('sitetree'));

      if ($form->isValid()) 
      {
        $form->save();

        // tell the manager the sitetree has changed so we can refresh the cache
        siteManager::getInstance()->sitetreeChanged();

        $sitetree = $form->getObject();
        
        $this->getUser()->setFlash('notice', '"' . $sitetree->getTitle() . '" was created');
        
        if ($sitetree->isManagedModule()) 
        {
          // redirect to the managing module editing action
          $moduleDefinition = $sitetree->getModuleDefinition();
          $moduleEditUrl = "{$moduleDefinition['admin_url']}?routeName=$sitetree->route_name&site=$sitetree->site";
          $this->redirect($moduleEditUrl);
        } 
        else 
        {
          $this->redirect('sitetree/index');
        }
      }
    }

    $this->form = $form;
    $this->parentId = $parentId;
    $this->parent = $parent;
  }
  
  /**
   * Publish a sitetree node
   */
  public function executePublish(sfWebRequest $request) 
  {
    $this->forward404Unless($this->canPublish, "Don't have permission to publish sitetree");
    
    $sitetree = SitetreeTable::getInstance()->findOneById($request->getParameter('id'));
    $this->forward404Unless($sitetree, 'No sitetree to publish');

    $sitetree->publish();
    siteManager::getInstance()->sitetreeChanged();

    $this->getUser()->setFlash('notice', '"'.$sitetree->getTitle().'" is now live');
    
    $this->redirect('sitetree/index');
  }
  
  /**
   * Delete a sitetree node
   */
  public function executeDelete(sfWebRequest $request) 
  {
    $this->forward404Unless($this->canAdmin, "Don't have permission to delete sitetree");
    
    $sitetree = SitetreeTable::getInstance()->findOneById($request->getParameter('id'));
    
    $this->forward404Unless($sitetree, 'No sitetree to delete');
    
    $title    = $sitetree->getTitle();

    if (!$sitetree->getNode()->isRoot()) 
    {
      // we don't allow deletion of the root node
      if ($sitetree->is_locked && !$this->getUser()->isSuperAdmin()) 
      {
        $this->getUser()->setFlash('error', 'Cannot delete locked page unless superadmin');
      }
      else 
      {
        if ($this->getUser()->isSuperAdmin() && $sitetree->is_deleted) 
        {
          // Perm delete page
          $sitetree->processDelete();
          siteManager::getInstance()->sitetreeChanged();

          $this->getUser()->setFlash('notice', '"'.$title.'" PERMANENTLY deleted');
        }
        else 
        {
          $sitetree->processDelete();
          siteManager::getInstance()->sitetreeChanged();
  
          $this->getUser()->setFlash('notice', '"'.$title.'" (and children) deleted');
        }
      }
    }
    else 
    {
      $this->getUser()->setFlash('error', 'Cannot delete root node!');
    }

    $this->redirect('sitetree/index');
  }
  
  /**
   * Restore a sitetree node
   */
  public function executeRestore(sfWebRequest $request) 
  {
    $this->forward404Unless($this->canAdmin, "Don't have permission to restore sitetree");
    
    $sitetree = SitetreeTable::getInstance()->findOneById($request->getParameter('id'));
    
    $this->forward404Unless($sitetree, 'No sitetree to restore');
    
    $title    = $sitetree->getTitle();

    if ($sitetree->is_deleted) 
    {
      // Cannot restore if parent node still deleted.
      if ($parent = $sitetree->getNode()->getParent()) 
      {
        if ($parent->is_deleted) 
        {
          $this->getUser()->setFlash('error', 'Restore the parent page first to restore this page');
        }
        else 
        {
          $sitetree->restore();
          siteManager::getInstance()->sitetreeChanged();
           
          $this->getUser()->setFlash('notice', '"'.$title.'" (and children) restored');
        }
      }
    }

    $this->redirect('sitetree/index');
  }
  
  /**
   * Move page up or down within its siblings
   * 
   * @param sfWebRequest $request
   */
  public function executeMove(sfWebRequest $request) 
  {
    $direction  = $request->getParameter('direction');
    $sitetree   = SitetreeTable::getInstance()->findOneById($request->getParameter('id'));
    
    $this->forward404Unless($sitetree, 'No sitetree to move');
    
    $node       = $sitetree->getNode();
    
    switch ($direction)
    {
      case 'up':
        if ($node->hasPrevSibling())
        {
          $prev = $node->getPrevSibling();
          $node->moveAsPrevSiblingOf($prev);
          
          $this->getUser()->setFlash('notice', "$sitetree->title swapped with $prev->title in sitetree");
        }
        break;
      case 'down':
        if ($node->hasNextSibling())
        {
          $next = $node->getNextSibling();
          $node->moveAsNextSiblingOf($next);
          
          $this->getUser()->setFlash('notice', "$sitetree->title swapped with $next->title in sitetree");
        }
        break;
    }

    siteManager::getInstance()->sitetreeChanged();
    $this->redirect('sitetree/index');
  }
  
  /**
   * Change parent of sitetree node
   * 
   * @param sfWebRequest $request
   */
  public function executeChangeParent(sfWebRequest $request)
  {
    $sitetree = SitetreeTable::getInstance()->findOneById($request->getParameter('id'));
    $this->forward404Unless($sitetree);

    $form = new changeSitetreeForm($sitetree);

    if (($request->isMethod(sfWebRequest::POST) || $request->isMethod(sfWebRequest::PUT)) && $request->hasParameter('sitetree')) 
    {
      // form was submitted
      $form->bind($request->getParameter('sitetree'));

      if ($form->isValid()) 
      {
        $form->save();
        
        // tell the manager the sitetree has changed so we can refresh the cache
        siteManager::getInstance()->sitetreeChanged();
        
        $this->getUser()->setFlash('notice', "'$sitetree->title' has been relocated");
        $this->redirect('sitetree/index');
      }
    }

    $this->form = $form;
  }
}
