<?php

class myUser extends cmsSecurityUser
{
  /**
   * Signs in the user on the application.
   * 
   * OVERRIDDEN: To fix cookies
   *
   * @param sfGuardUser         $user     The sfGuardUser id
   * @param boolean             $remember Whether or not to remember the user
   * @param Doctrine_Connection $con      A Doctrine_Connection object
   */
  public function signIn($user, $remember = false, $con = null)
  {
    // save last login
    $user->setLastLogin(date('Y-m-d H:i:s'));
    $user->save($con);

    // signin
    $this->setAttribute('user_id', $user->getId(), 'sfGuardSecurityUser');
    $this->setAuthenticated(true);
    $this->clearCredentials();
    $this->addCredentials($user->getAllPermissionNames());

    // remember?
    if ($remember)
    {
      $expiration_age = sfConfig::get('app_sf_guard_plugin_remember_key_expiration_age', 15 * 24 * 3600);

      // remove old keys
      Doctrine_Core::getTable('sfGuardRememberKey')->createQuery()
        ->delete()
        ->where('created_at < ?', date('Y-m-d H:i:s', time() - $expiration_age))
        ->execute();

      // remove other keys from this user
      Doctrine_Core::getTable('sfGuardRememberKey')->createQuery()
        ->delete()
        ->where('user_id = ?', $user->getId())
        ->execute();

      // generate new keys
      $key = $this->generateRandomKey();

      // save key
      $rk = new sfGuardRememberKey();
      $rk->setRememberKey($key);
      $rk->setUser($user);
      $rk->setIpAddress($_SERVER['REMOTE_ADDR']);
      $rk->save($con);

      // make key as a cookie
      $remember_cookie = sfConfig::get('app_sf_guard_plugin_remember_cookie_name', 'sfRemember');
      
      // UPDATED: To Ensure sfRemember me cookie is Secure and HTTP only
      $secure = (sfConfig::get('sf_environment') != 'dev');
      sfContext::getInstance()->getResponse()->setCookie($remember_cookie, $key, time() + $expiration_age, '/', '', $secure, true);
    }
  }
  
  /**
   * Signs out the user.
   * 
   * OVERIDDEN: To fix incorrect log out
   *
   */
  public function signOut()
  {
    // reset and clear everything
    $this->user = null;
    $this->clearCredentials();
    $this->setAuthenticated(false);
    $this->getAttributeHolder()->clear();

    // undo sfRememberMe
    $expiration_age = sfConfig::get('app_sf_guard_plugin_remember_key_expiration_age', 15 * 24 * 3600);
    $remember_cookie = sfConfig::get('app_sf_guard_plugin_remember_cookie_name', 'sfRemember');
    
    // UPDATED: To Ensure sfRemember me cookie is Secure and HTTP only
    $secure = (sfConfig::get('sf_environment') != 'dev');
    sfContext::getInstance()->getResponse()->setCookie($remember_cookie, '', time() - $expiration_age, '/', '', $secure, true);
    
    // For good measure - regen session
    if (sfConfig::get('sf_environment') != 'test')
    {
      session_destroy();
      session_write_close();
      session_regenerate_id();
    }
  }
}
