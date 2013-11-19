<?php

/**
 * iframeAdmin actions.
 *
 * @package    site_cms
 * @subpackage iframeAdmin
 * @author     Jo Carter
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class iframeAdminActions extends sfActions
{
  public function preExecute()
  {
    // Permissions
    $user = sfContext::getInstance()->getUser();
    $this->canAdmin   = $user->hasCredential('site.admin');
    $this->canPublish = ($this->canAdmin || $user->hasCredential('site.publish'));
  }
  
  /**
   * Edit an iframe by routeName
   *
   * If the iframe does not exist it creates it and redirects the user
   * to the edit iframe page
   */
  public function executeEditByRoute(sfWebRequest $request)
  {
    $this->forward404Unless($this->hasRequestParameter('routeName') && $this->hasRequestParameter('site'));

    $routeName = $this->getRequestParameter('routeName');
    $site      = $this->getRequestParameter('site');

    $sitetree  = SitetreeTable::getInstance()->retrieveByRoutename($site, $routeName);
    $iframe    = IframeTable::getInstance()->findOneBySitetreeId($sitetree->id);

    if (!$iframe)
    {
      $iframe  = Iframe::createFromSitetree($sitetree);
      $iframe->save();
    }
    
    $this->redirect("iframeAdmin/edit?id=$iframe->id");
  }

  /**
   * Edit an iframe
   */
  public function executeEdit(sfWebRequest $request)
  {
    $this->forward404Unless($this->hasRequestParameter('id'));

    $iframeId = $request->getParameter('id');
    $iframe   = IframeTable::getInstance()->findOneById($iframeId);

    $this->forward404Unless($iframe);

    $sitetree = SitetreeTable::getInstance()->findOneById($iframe->sitetree_id);
    $this->setVar('sitetree', $sitetree, true);
    
    $this->form = new IframeForm($iframe);
    
    if ($request->hasParameter('iframe'))
    {
      $this->form->bind($request->getParameter('iframe'));
      
      if ($this->form->isValid())
      {
        $this->form->save();
        
        $this->getUser()->setFlash('notice', 'iFrame content saved');
        
        $this->redirect("iframeAdmin/edit?id=$iframe->id");
      }
    }
  }
}
