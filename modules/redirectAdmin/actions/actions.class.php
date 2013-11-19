<?php

/**
 * redirect admin actions.
 *
 * @package    site_cms
 * @subpackage redirectAdmin
 * @author     Jo Carter
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class redirectAdminActions extends sfActions
{
  public function preExecute()
  {
    // Permissions
    $user = sfContext::getInstance()->getUser();
    $this->canAdmin   = $user->hasCredential('site.admin');
    $this->canPublish = ($this->canAdmin || $user->hasCredential('site.publish'));
  }
  
  /**
   * Edit a redirect by routeName
   * 
   * If the redirect doesn't exist, it creates it then redirect to the edit page
   */
  public function executeEditByRoute(sfWebRequest $request)
  {
    $this->forward404Unless($this->hasRequestParameter('routeName'));
    $this->forward404Unless($this->hasRequestParameter('site'));

    $routeName = $this->getRequestParameter('routeName');
    $site      = $this->getRequestParameter('site');

    $sitetree = SitetreeTable::getInstance()->retrieveByRoutename($site, $routeName);
    $redirect = RedirectTable::getInstance()->findOneBySitetreeId($sitetree->id);

    if (!$redirect)
    {
      $redirect = Redirect::createFromSitetree($sitetree);
      $redirect->save();
    }
    
    $this->redirect("redirectAdmin/edit?id=$redirect->id");
  }
  
  /**
   * Edit a redirect
   */
  public function executeEdit(sfWebRequest $request)
  {
    $this->forward404Unless($this->hasRequestParameter('id'));

    $redirectId = $request->getParameter('id');
    $redirect   = RedirectTable::getInstance()->findOneById($redirectId);

    $this->forward404Unless($redirect);

    $sitetree = SitetreeTable::getInstance()->findOneById($redirect->sitetree_id);
    $this->setVar('sitetree', $sitetree, true);
    
    $this->form = new RedirectForm($redirect);
    
    if ($request->hasParameter($this->form->getName()))
    {
      $this->form->bind($request->getParameter($this->form->getName()));
      
      if ($this->form->isValid())
      {
        $this->form->save();
        
        $this->getUser()->setFlash('notice', 'Redirect saved');
        
        $this->redirect("redirectAdmin/edit?id=$redirect->id");
      }
    }
  }
}
