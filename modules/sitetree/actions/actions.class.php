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
  }
  
  /**
   * Change the language in the CMS
   */
  public function executeLanguage(sfWebRequest $request)
  {
    $this->getUser()->setCulture($this->getRequestParameter('sf_culture'));
    $url = ($this->getRequest()->getReferer() != '' ? $this->getRequest()->getReferer() : '@homepage');
    $this->redirect($url);
  }
  
   /**
   * Executes index action
    */
  public function executeIndex(sfWebRequest $request) 
  {
    $manager           = siteManager::getInstance();
    $site              = $manager->getCurrentSite();
    $this->treeNodes   = $manager->getEntireSitetree($site);
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
          $moduleEditUrl = "{$moduleDefinition['admin_url']}?routeName=$sitetree->route_name";
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
    $sitetree = SitetreeTable::getInstance()->findOneById($request->getParameter('id'));
    $this->forward404Unless($sitetree, 'No sitetree to delete');
    $title = $sitetree->getTitle();

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
          $sitetree->delete();
          siteManager::getInstance()->sitetreeChanged();

          $this->getUser()->setFlash('notice', '"'.$title.'" PERMANENTLY deleted');
        }
        else 
        {
          $sitetree->delete();
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
    $sitetree = SitetreeTable::getInstance()->findOneById($request->getParameter('id'));
    $this->forward404Unless($sitetree, 'No sitetree to restore');
    $title = $sitetree->getTitle();

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
}
