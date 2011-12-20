<?php
/**
 * Extending Doctrine_Template_Listener_Blameable to use symfony sf_guard_user id
 * 
 * @author Jo Carter <work@jocarter.co.uk>
 */
class siteBlamableListener extends Doctrine_Template_Listener_Blameable
{
  /**
   * @return int $ident sf_guard_user.id
   */
  public function getUserIdentity() 
  {
    // If on the command line - don't break :)
    if (PHP_SAPI === 'cli') 
    {
      $ident = 1;     // Admin
    }
    else 
    {
      $ident = sfContext::getInstance()->getUser()->getGuardUser()->getId();
    }

    return $ident;
  }
}