<?php

/**
 * redirectDisplay actions.
 *
 * @package    site_cms
 * @subpackage redirectDisplay
 * @author     Jo Carter
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class redirectDisplayActions extends sfActions
{
  /**
   * Display a redirect
   *
   * This looks up the current sitetree node and returns the redirect linked to it.
   */
  public function executeIndex()
  {
    // find sitetree node from route matched
    $siteManager  = siteManager::getInstance();
    $sitetreeNode = $siteManager->initCurrentSitetreeNode();

    if (!$sitetreeNode)
    {
      $this->forward404('No sitetree node matched the current request');
    }

    // find redirect from sitetree node
    $redirect = RedirectTable::getInstance()->findOneBySitetreeId($sitetreeNode->id);
    $this->forward404Unless(($redirect && $redirect->url), 'No redirect could be found for this sitetree');

    $this->redirect($redirect->url, $redirect->status_code);
  }
}
