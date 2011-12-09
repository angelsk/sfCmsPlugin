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
        
        // load up the Content blocks for this group and pass them to the view.
        // this will also create any missing Content blocks etc.
        $checkDefinitions = true;
        $contentBlocks = $contentGroup->getOrderedBlocks($checkDefinitions);
        
        // if form submitted
        if ($request->isMethod(sfWebRequest::POST) 
        			&& ($request->hasParameter('save') || $request->hasParameter('save_and_publish'))) 
		{
        	$editingContentBlockVersions = $this->getEditingContentBlockVersions($contentGroup, $contentBlocks);
        	
        	if (!$editingContentBlockVersions) 
			{
        		sfContext::getInstance()->getUser()->setFlash('Content_error', 'The versions you were working on were deleted while you were working - you will need to refresh the page and try editing again');
        	}
        	else 
			{
        		$canSave = true;
        		$canPublish = ($request->hasParameter('save_and_publish') ? true : false);
        		$contentChanged = false;
        		$contentToSave = array();
        		$flash = '';
        		
        		// Validate Content
        		foreach ($contentBlocks as $contentBlock) 
				{
        			$identifier = $contentBlock->identifier;
		            $contentBlockVersion = $editingContentBlockVersions[$identifier];
		            $contentBlockType = $contentBlockVersion->getContentBlockType();
		            
		            if (!$contentBlockType->editIsValid($request)) 
					{
		            	$canSave = false;
		            	$canPublish = false;
		            }
		            else 
					{
		            	if ($contentBlockType->editIsChanged($request)) 
						{
		            		$contentChanged = true;
		            		$contentToSave[] = $contentBlock;
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
        			
        			$flash .= count($contentToSave) . ' Content block(s) were saved';
        			$canPublish = true;
        		}
        		elseif (!$canSave) 
				{
	            	$user->setFlash('Content_error', 'Content couldn\'t be saved - please correct the errors and try again');
	            }
	            elseif (!$canPublish) 
				{
	            	$user->setFlash('Content_notice', 'No Content was changed');
	            }
        			
        		if ($canPublish && $request->hasParameter('save_and_publish')) 
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
        				$flash = 'All Content currently published';
        			}
        			else 
					{
	        			if (!empty($flash)) $flash .= ' and ';
	        			$flash .= $published . ' Content block(s) were published';
        			}
        		}
        		
        		if (!empty($flash)) $user->setFlash('Content_notice', $flash);
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
        
        $previewUrl = $contentGroup->getContentGroupType()->getPreviewUrl();
        $previewUrl = siteManager::getInstance()->generateCrossAppUrlFor($previewUrl);

        $this->setVar('previewUrl', $previewUrl);
        $this->setVar('ContentBlocks', $contentBlocks);
        $this->setVar('ContentBlockVersions', $contentBlockVersions);
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
        	$editingContentBlock = $this->getRequestParameter("Content_block_{$contentBlock->id}");
        	$editingContentBlockVersionId = $editingContentBlock['version_id'];
        	
            //$editingContentBlockVersionId = $this->getRequestParameter("Content_block_{$contentBlock->id}_version_id");
            $contentBlockVersion = ContentBlockVersionTable::getInstance()->findOneById($editingContentBlockVersionId);
            
            if (!$contentBlockVersion) 
			{
                // no such Content block version exists anymore
                return false;
            }

            // we need to check that the $contentBlockVersion matches the $contentBlock
            if ($contentBlock->id != $contentBlockVersion->Content_block_id) 
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
            $identifier = $contentBlock->identifier;
            $contentBlockVersion = $editingContentBlockVersions[$identifier];
            $contentBlockType = $contentBlockVersion->getContentBlockType();

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
