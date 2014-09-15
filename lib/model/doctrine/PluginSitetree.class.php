<?php

/**
 * PluginSitetree
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    sfCmsPlugin
 * @subpackage model
 * @author     Jo Carter <work@jocarter.co.uk>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class PluginSitetree extends BaseSitetree
{
  /**
   * @HACK ALERT!
   *
   * The doctrine nested set implementation currently wants us to have an integer
   * id for the root_id field, but we don't want this (because a string it a much
   * easier site identifier).  So, we force it to use a string here.
   * However, this does break a couple of the internals of the nested
   * set, namely the Doctrine_Tree_NestedSet::getNextRootId() method.  This is only
   * needed if you want to create a new root without specifying the site, though.
   */
  public function setUp()
  {
    parent::setUp();
    $this->hasColumn('site', 'string', 3);
  }


  /**
   * Create root node for the site - required for all sites, called on initial page load of admin section
   * @param string $module
   */
  public static function createRoot($site, $module)
  {
    $rootNode = new Sitetree();

    // Root name unique per site - makes it easier to manage
    // Use the symfony default to override the default homepage routename
    $rootNode->fromArray(array(
      'route_name'    => 'homepage',
      'site'          => $site,
      'base_url'      => '',
      'lft'           => 1,
      'rgt'           => 2,
      'level'         => 0,
      'target_module' => $module
    ));

    $rootNode->setTitle('Home');
    $rootNode->save();

    return $rootNode;
  }

  /**
   * Get the root node for the site
   *
   * @param string $site
   * @return Sitetree
   */
  public static function getRoot($site)
  {
    if ($root = SitetreeTable::getInstance()->getTree()->fetchRoot($site))
    {
      return $root;
    }
    else
    {
      $defn = siteManager::getInstance()->getSite();
      $rootModule = isset($defn['root_module']) ? $defn['root_module'] : 'default';
      return Sitetree::createRoot($site, $rootModule);
    }
  }

  /**
   * Get the internal, symfony url for this sitetree
   *
   * @param string $name
   * @param array $params
   * @return string
   */
  public function getSymfonyUrl($name = '', $params = array())
  {
    return siteManager::getInstance()->getRoutingProxy()->generateInternalUrl($this, $name, $params);
  }

  /**
   * Add the given sitetree into the given router
   *
   * @param Sitetree $sitetree (array)
   * @param siteRoutingProxy $routingProxy
   * @param string $junkChar
   * @param array $urlStack
   */
  public static function addToRouting($sitetree, $routingProxy, $junkChar, $urlStack)
  {
    // add in a simple route
    $url = self::makeUrl($sitetree, $urlStack);
    $params = array('module' => $sitetree['target_module'], 'action' => 'index');

    $routingProxy->addRoute($sitetree, '', $url, $params);
  }

  /**
   * Make a url for a sitetree and a urlStack
   *
   * @param Sitetree $sitetree
   * @param array $urlStack
   * @return string
   */
  public static function makeUrl($sitetree, $urlStack)
  {
    if ($sitetree['level'] == 0)
    {
      return '/';
    }

    $url = '';
    $i = 1;

    if ($sitetree['prepend_parent_url'])
    {
      while ($i < $sitetree['level'])
      {
        if (array_key_exists($i, $urlStack))
        {
          $url .= '/' . trim($urlStack[$i], '/');
        }

        $i++;
      }
    }

    $url .= '/' . $sitetree['base_url'];

    return $url;
  }

  /**
   * Get localised title
   *
   * @return string
   */
  public function getTitle()
  {
    $lang = sfContext::getInstance()->getUser()->getCulture();
    $title = $this->Translation[$lang]->title;

    // Don't return blank title - return default culture version if translation not available
    if (!is_null($title))  return $title;
    else
    {
      // Try default language of the site
      $defn         = siteManager::getInstance()->getSite();
      $default_lang = $defn['default_culture'];
      $title        = $this->Translation[$default_lang]->title;

      if (!is_null($title)) return $title;
      else
      {
        // Return first language so we have something
        return $this->Translation->getFirst()->title;
      }
    }
  }

  /**
   * Is this a managed module?
   *
   * @return boolean
   */
  public function isManagedModule()
  {
    $moduleDefinition = $this->getModuleDefinition();

    if ($moduleDefinition === null)
    {
      return false;
    }

    if (!isset($moduleDefinition['admin_url']) || !$moduleDefinition['admin_url'])
    {
      return false;
    }

    return ($this->getModuleDefinition() !== null);
  }

  /**
   * Get the definition of the module this sitetree node represents (if it is not a custom one etc.)
   *
   * @return array
   */
  public function getModuleDefinition()
  {
    return siteManager::getInstance()->getModuleDefinition($this->target_module);
  }

  /**
   * Get a link for editing this sitetree node - only applies to managed modules
   *
   * @return string
   */
  public function getEditLink()
  {
    if (!$this->isManagedModule())
    {
      return '';
    }

    $moduleDefinition = $this->getModuleDefinition();

    return $moduleDefinition['admin_url'] . "?routeName=$this->route_name&site=$this->site";
  }

  /**
   * Dispatch a sitetree event to the managed module's event handler.
   *
   * This is used for delete, copy etc.
   *
   * @param siteEvent $event
   */
  public function dispatchSiteEvent($event)
  {
    if (!$this->isManagedModule())
    {
      // nothing to inform about this
      return;
    }

    $moduleDefinition = $this->getModuleDefinition();

    if (!isset($moduleDefinition['event_handler']))
    {
      // we don't have an event handler for this module
      return;
    }

    $res = call_user_func($moduleDefinition['event_handler'], $event);
  }

  /**
   * Handle the site events
   *
   * @param siteEvent $event
   */
  public static function siteEventHandler($event)
  {
    if ($event->getName() == siteEvent::SITETREE_ROUTING)
    {
      // handle the routing... ie, register our routes.
      $sitetree = $event->getSubject();
      $params = $event->getParameters();
      $routingProxy = $params['routingProxy'];
      $urlStack = $params['urlStack'];

      $nodeUrl = Sitetree::makeUrl($sitetree, $urlStack);

      // add in index route
      $routingProxy->addRoute(
                          $sitetree,
                          '',
                          $nodeUrl,
                          array('module' => 'sitemap', 'action' => 'index')
        );

      // add in sitemap xml route
      $routingProxy->addRoute(
                          $sitetree,
                          'xml',
                          '/sitemap.xml',
                          array('module' => 'sitemap', 'action' => 'sitemap')
        );
    }
  }

  /**
   * Publish node
   */
  public function publish()
  {
    $this->set('is_active', true);
    $this->save();
  }

  /**
   * Delete this sitetree node
   * This should be called from any external code rather than delete();
   *
   * Check whether set as deleted first - if not, then soft delete
   * If is and user is superadmin - delete permenantly
   */
  public function processDelete(Doctrine_Connection $conn = null)
  {
    if (!$this->is_deleted)
    {
      $this->softDelete();
    }
    else if (sfContext::getInstance()->getUser()->isSuperAdmin())
    {
      $event = new siteEvent($this, siteEvent::SITETREE_DELETE);
      $this->dispatchSiteEvent($event);

      // Make sure delete sitetreeNode as otherwise there will be nesting issues
      // this calls parent::delete();
      $this->deleteNode();

      // delete translations
      $translations = $this->Translation;

      foreach ($translations as $lang => $translation)
      {
        $translation->delete();
        $translation->free();
      }
    }
  }


  /**
   * Restore a soft deleted sitetree node
   */
  public function restore()
  {
    $this->set('is_deleted', false);
    $this->set('is_active', false);
    $this->set('deleted_by', null);
    $this->set('deleted_at', null);
    $this->save();

    // Needs to do the same for all children of the node
    if ($this->getNode()->hasChildren())
    {
      foreach ($this->getNode()->getChildren() as $child)
      {
        $child->restore();
      }
    }
  }

  /**
   * Don't actually delete, just set AS deleted
   * For auditing purposes
   */
  public function softDelete()
  {
    $user = sfContext::getInstance()->getUser();

    $this->set('is_deleted', true);
    $this->set('is_active', false);
    $this->set('deleted_by', $user->getGuardUser()->getId());
    $this->set('deleted_at', date('Y-m-d H:i:s'));
    $this->save();

    // Needs to do the same for all children of the node
    if ($this->getNode()->hasChildren())
    {
      foreach ($this->getNode()->getChildren() as $child)
      {
        $child->softDelete();
      }
    }

    // Don't send event as this nukes all the content from the page
  }

  /**
   * Create a copy of this sitetree node and insert it under/next to $otherSitetreeNode
   *
   * They can be in different sites, and this will most often be used to copy the entire
   * sitetree from one site to a new site
   *
   * @param SiteTree $copyHere Sitetree node that this is to be copied to
   * @param boolean $isUnder Whether the sitetree cloned node becomes a child or a sibling of $copyTo
   * @param boolean $copyChilden Whether to copy the children of the current node too
   * @param boolean $isChildCopy This is currently one of the child copies (recursive function)
  */
  public function copyTo($copyHere, $isUnder = true, $copyChilden = true, $isChildCopy = false)
  {
    // Don't copy root node - just copy children
    if (!$this->getNode()->isRoot())
    {
      // Check module allowed in new site
      $availableModules = siteManager::getInstance()->getAvailableModules();

      if (!in_array($this->target_module, $availableModules)) {} // skip
      else
      {
        $this->refreshRelated('Translation');
        $copy = $this->copy(true);

        // make routename of target item unique
        $limit        = 20;
        $count        = 1;
        $newRouteName = $copy->route_name;

        while ($count <= $limit)
        {
          $nodeExists = SitetreeTable::getInstance()->findOneBySiteAndRouteName($copyHere->site, $newRouteName);

          if (!$nodeExists)
          {
            break; // we have a unique routename!
          }

          // try a new one
          $newRouteName = $copy->route_name . $count++;
        }

        $copy->route_name = $newRouteName;
        $copy->site       = $copyHere->site;
        $copy->is_active  = false;  // copy all as inactive until they have content

        // clear current tree fields otherwise INSERT will complain
        $copy->level = $copy->rgt = $copy->lft = null;

        if ($isUnder)
        {
          if ($isChildCopy)
          {
            $copy->getNode()->insertAsLastChildOf($copyHere);
          }
          else
          {
            $copy->getNode()->insertAsFirstChildOf($copyHere);
          }
        }
        else
        {
          $copy->getNode()->insertAsNextSiblingOf($copyHere);
        }

        // get the module at this node to duplicate itself
        // no content - but page / listing configuration (plus anything in custom modules)
        $event = new siteEvent(
          $this,
          siteEvent::SITETREE_COPY,
          array(
            'copyTo' => $copy
          )
        );

        $this->dispatchSiteEvent($event);
      }
    }
    else
    {
      $copy = $copyHere;
    }

    if ($copyChilden)
    {
      $this->refresh();

      if ($children = $this->getNode()->getChildren())
      {
        foreach ($children as $child)
        {
          if (!$child->is_deleted) // don't copy any nodes which have been deleted
          {
            $child->refresh();
            $child->copyTo($copy, true, true, true);
            $copy->refresh();
          }
        }
      }
    }

    return $copy;
  }

  /**
   * Does this page share a URL with another page on the site
   *
   * @param string $url
   */
  public function hasConflictedUrl($url, $culture)
  {
    $manager   = siteManager::getInstance();
    $culture   = sfContext::getInstance()->getUser()->getCulture();
    $sitetrees = SitetreeTable::getInstance()->getInstance()->findBySiteAndBaseUrlAndIsDeleted($this->site, $this->base_url, false);

    foreach ($sitetrees as $sitetree)
    {
      if ($sitetree->id == $this->id) continue; // don't check this page!

      $urlC = $manager->generateCrossAppUrlFor($manager->getRoutingProxy()->generateInternalUrl($sitetree, '', array('sf_culture' => $culture)), $manager->getManagedApp());

      if ($urlC == $url) return true;
    }

    return false;
  }
}