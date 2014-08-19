<?php

/**
 * Content blocks editing components.
 *
 * @package    site_cms
 * @subpackage ContentAdmin
 * @author     Jo Carter
 * @version    SVN: $Id: components.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class ContentAdminComponents extends sfComponents
{
  /**
   * Displays an editor for a Content block.  The editor does all of its work
   * over AJAX, so you can drop this into any page.
   *
   * Required parameters:
   * @param $contentGroup
   */
  public function executeEditor(sfWebRequest $request)
  {
    $contentGroup = $this->contentGroup;

    if (!$contentGroup instanceof ContentGroup)
    {
      throw new sfException('Missing ContentGroup parameter');
    }

    $sitetree = $contentGroup->getContentGroupType()->getSitetree();

    if (!$sitetree instanceof Sitetree)
    {
      throw new sfException("Invalid Sitetree from ContentGroup");
    }

    $user = sfContext::getInstance()->getUser();
    
    // Permissions
    $this->canAdmin   = $user->hasCredential('site.admin');
    $this->canPublish = ($this->canAdmin || $user->hasCredential('site.publish'));
    
    // Object info
    $type     = $contentGroup->getType();
    $getType  = 'get' . $type;
    $obj      = $contentGroup->$getType()->getFirst();
    
    // Get approval information
    $this->currentApproval = SiteApprovalTable::getInstance()->findLatest($type, $obj->getPrimaryKey());
    
    // Is this a new content group? No content yet
    $loadContent         = true;
    $this->includeImport = false;
    $isNew               = $contentGroup->isNew();
    $this->activeSites   = siteManager::getInstance()->getActiveSites();
    
    // Don't import content
    if (($request->isMethod(sfWebRequest::POST) || $request->isMethod(sfWebRequest::PUT)) && $request->hasParameter('dont_import'))
    {
      $isNew = false;
      $user->setFlash('content_notice', 'Import skipped, carry on');
    }
    
    // Import content
    if (($request->isMethod(sfWebRequest::POST) || $request->isMethod(sfWebRequest::PUT)) && $request->hasParameter('import'))
    {
      $importContentGroupId = $request->getParameter('import_content_group_id', null);
      
      if (!empty($importContentGroupId))
      {
         // Import content from selected content group
         $contentBlocks = $contentGroup->createFrom($importContentGroupId);
         
         $loadContent = false;
         $isNew       = false;
         $user->setFlash('content_notice', 'Content imported and published');
      }
      else
      {
        $user->setFlash('content_error', 'No content selected to import; select from the dropdown or choose "Don\'t import"');
      }
    }
    
    // Do we have another site to copy content from? (for Page and Listing) - publishers only
    if ($this->canPublish && $isNew 
                && !empty($this->activeSites) && 1 < count($this->activeSites) 
                && (in_array($type, array('Listing', 'Page'))))
    {
      // Get other objects of this template
      $this->objs = Doctrine_Core::getTable($type)
                            ->createQuery('o')
                            ->innerJoin('o.Sitetree s')
                            ->where('o.template = ? AND s.site != ?', array($obj->template, $sitetree->site))
                            ->execute();
      
      if (0 < count($this->objs))
      {
        $this->includeImport = true;
        $loadContent         = false;
        $contentBlocks       = array();
      }
    }
    
    if ($loadContent)
    {
      // load up the Content blocks for this group and pass them to the view.
      // this will also create any missing Content blocks etc.
      $checkDefinitions = true;
      $contentBlocks = $contentGroup->getOrderedBlocks($checkDefinitions);
    }
    
    // if form submitted
    if (($request->isMethod(sfWebRequest::POST)  || $request->isMethod(sfWebRequest::PUT))
      && ($request->hasParameter('save') || $request->hasParameter('save_and_publish')))
    {
      $editingContentBlockVersions = $this->getEditingContentBlockVersions($contentGroup, $contentBlocks);

      if (!$editingContentBlockVersions)
      {
        sfContext::getInstance()->getUser()->setFlash('content_error', 'The content you were working on is not valid - you will need to refresh the page and edit it again');
      }
      else
      {
        $canSave        = true;
        $canPutLive     = ($request->hasParameter('save_and_publish') ? true : false);
        $contentChanged = false;
        $contentToSave  = array();
        $flash          = '';

        // Validate Content
        foreach ($contentBlocks as $contentBlock)
        {
          $identifier = $contentBlock->identifier;
          $contentBlockVersion = $editingContentBlockVersions[$identifier];
          $contentBlockType = $contentBlockVersion->getContentBlockType();

          if (!$contentBlockType->editIsValid($request))
          {
            $canSave    = false;
            $canPutLive = false;
          }
          else
          {
            if ($contentBlockType->editIsChanged($request))
            {
              $contentChanged   = true;
              $contentToSave[]  = $contentBlock;
            }
          }
        }
        
        if ($canSave && $contentChanged)
        {
          // Save Content
          $newVersions = $this->saveContentBlocksFromRequest($contentToSave, $editingContentBlockVersions, $request);

          // change our current editing versions to be these new ones
          foreach ($newVersions as $identifier => $contentBlockVersion)
          {
            $editingContentBlockVersions[$identifier] = $contentBlockVersion;
          }

          $flash .= sprintf('%s content block%s saved', count($contentToSave), (count($contentToSave) == 1 ? ' was' : 's were'));
          $canPutLive = true;
        }
        elseif (!$canSave)
        {
          $user->setFlash('content_error', "Content couldn't be saved - please correct the errors and try again");
        }
        elseif (!$canPutLive)
        {
          $user->setFlash('content_notice', 'No content was changed');
        }

        if ($canPutLive && $request->hasParameter('save_and_publish'))
        {
          // If canPublish then publish
          if ($this->canPublish)
          {
            $published = 0;
  
            foreach ($contentBlocks as $contentBlock)
            {
              $identifier = $contentBlock->identifier;
              $contentBlockVersion = $editingContentBlockVersions[$identifier];
              $contentBlockType = $contentBlockVersion->getContentBlockType();
  
              if ($contentBlockVersion->isCurrent())
              {
                // this version is already live, so no need for action
                continue;
              }
              else
              {
                $contentBlock->makeVersionCurrent($contentBlockVersion);
                $published++;
              }
            }
  
            if (0 == $published)
            {
              $flash .= sprintf('%s content already published', (!empty($flash) ? ' and all' : 'All'));
            }
            else
            {
              // see if there's a current one and delete that
              if ($this->currentApproval) $this->currentApproval->delete();
              
              $flash .= sprintf('%s %s content block%s published', (!empty($flash) ? ' and ' : ''), $published, ($published == 1 ? ' was' : 's were'));
            }
          }
          // Else mark for approval
          else if ($contentChanged)
          {
            // see if there's a current one and delete that
            if ($this->currentApproval) $this->currentApproval->delete();
            
            // create a new one
            $this->currentApproval = new SiteApproval();
            $this->currentApproval->fromArray(array(
              'model'       => $type,
              'model_id'    => $obj->getPrimaryKey(),
              'sitetree_id' => $sitetree->getPrimaryKey()
            ));
            $this->currentApproval->save();
            
            $flash .= ' and content marked for approval';
          }
          else $flash = 'No content was changed';
        }

        if (!empty($flash)) $user->setFlash('content_notice', $flash);
        
        $this->clearContentGroupCache($contentGroup);
      }
    }

    // load up the current Content block versions for these
    $contentBlockVersions = array();

    if (isset($editingContentBlockVersions) && !empty($editingContentBlockVersions)
            && ($request->hasParameter('save') || $request->hasParameter('save_and_publish')))
    {
      $contentBlockVersions = $editingContentBlockVersions;
    }
    else
    {
      foreach ($contentBlocks as $contentBlock)
      {
        $loaded = false;

        if ($request->hasParameter('load_version'))
        {
          $blockId = $request->getParameter('load_version_block_id');

          if ($contentBlock->id == $blockId)
          {
            $versionId = $request->getParameter('load_version_id');
            $contentBlockVersions[$contentBlock->identifier] = $contentBlock->getSpecifiedVersion($versionId);
            $loaded = true;
          }
        }

        if (!$loaded)
        {
          $verb = 'Current';
          if ($request->hasParameter('new_versions')) $verb = 'Newest';
          $method = "get{$verb}Version";

          $contentBlockVersions[$contentBlock->identifier] = $contentBlock->$method();
        }
      }
    }
    
    if ($request->hasParameter('clear_cache'))
    {
      $this->clearContentGroupCache($contentGroup);
      
      $user->setFlash('content_notice', 'Page cache cleared on ' . siteManager::getInstance()->getManagedApp());
    }

    $previewUrl = (siteManager::getInstance()->checkLock() ? false : siteManager::getInstance()->generateCrossAppUrlFor($contentGroup->getContentGroupType()->getPreviewUrl()));

    $this->setVar('previewUrl', $previewUrl);
    $this->setVar('contentBlocks', $contentBlocks, true);
    $this->setVar('contentBlockVersions', $contentBlockVersions, true);
    $this->setVar('contentGroup', $contentGroup, true);
  }

  /**
   * Load up the Content block versions we're currently editing from the request
   *
   * @param ContentGroup $contentGroup
   * @param ContentBlock[] $contentBlocks
   * @return ContentBlockVersion[]/false
   */
  protected function getEditingContentBlockVersions($contentGroup, $contentBlocks)
  {
    $editingContentBlockVersions = array();

    foreach ($contentBlocks as $contentBlock)
    {
      $editingContentBlock          = $this->getRequestParameter("content_block_{$contentBlock->id}");
      $editingContentBlockVersionId = $editingContentBlock['version_id'];
      $contentBlockVersion          = ContentBlockVersionTable::getInstance()->findOneById($editingContentBlockVersionId);

      if (!$contentBlockVersion)
      {
        // no such Content block version exists anymore
        return false;
      }

      // we need to check that the $contentBlockVersion matches the $contentBlock
      if ($contentBlock->id != $contentBlockVersion->content_block_id)
      {
        return false;
      }

      // check current lang matches that of the version
      if ($contentBlock->getCurrentLang() != $contentBlockVersion->lang)
      {
        return false;
      }

      $editingContentBlockVersions[$contentBlock->identifier] = $contentBlockVersion;
    }

    return $editingContentBlockVersions;
  }

  /**
   * Save these ContentBlocks from the request.  We already know that they are valid.
   *
   * @param ContentBlock[] $contentBlocksToSave
   * @param ContentBlockVersion[] $editingContentBlockVersions
   * @param sfWebRequest $request
   */
  protected function saveContentBlocksFromRequest($contentBlocksToSave, $editingContentBlockVersions, $request)
  {
    $newContentBlockVersions = array();

    foreach ($contentBlocksToSave as $contentBlock)
    {
      $identifier             = $contentBlock->identifier;
      $contentBlockVersion    = $editingContentBlockVersions[$identifier];
      $contentBlockType       = $contentBlockVersion->getContentBlockType();

      $newContentBlockVersion = ContentBlockVersion::createVersion($contentBlock, $contentBlockVersion->lang);

      $contentBlockType->editDuplicateAndSave($newContentBlockVersion, $request);
      $newContentBlockVersions[$identifier] = $newContentBlockVersion;

      // remove from request else incorrect id will get set to form
      $request->getParameterHolder()->remove($contentBlockType->getFormName());
    }

    return $newContentBlockVersions;
  }

  /**
   * Clear Content group view cache
   *
   * @param ContentGroup $contentGroup
   */
  protected function clearContentGroupCache($contentGroup)
  {
    $contentGroup->getContentGroupType()->handleContentGroupChanged();
  }
}
