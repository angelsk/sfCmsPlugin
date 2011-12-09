<?php

/**
 * ContentGroupType for a page
 *
 * This tells the page's Content group how to behave.
 */
class ContentGroupTypePage extends ContentGroupType 
{
    protected $page = null;
    
    /**
     * Get the page linked to the currently set Content group.  We can always do 
     * this because the page has the id of the Content group set.
     *
     * @return page
     */
    public function getPage() 
	{
        if ($this->page === null) 
		{
            $this->page = PageTable::getInstance()->findOneByContentGroupId($this->ContentGroup->id);
            
            if (!$this->page) 
			{
                throw new sfException("Missing a page");
            }
        }
        
        return $this->page;
    }
    
    /**
     * @see ContentGroupType
     */
    public function getContentBlockDefinitions() 
	{
        $templateSlug = $this->getPage()->template;
        return pageManager::getInstance()->getTemplateBlockDefinitions($templateSlug);
    }
    
    /**
     * @see ContentGroupType
     */
    public function getEditUrl() 
	{
        return 'pageAdmin/edit?id=' . $this->getPage()->id;
    }
    
    /**
     * @see ContentGroupType
     */
    public function getPreviewUrl() 
	{
        return 'pageDisplay/preview?id=' . $this->getPage()->id;
    }
    
    /**
     * @see ContentGroupType
     */
    public function getSitetree() 
	{
        $page = $this->getPage();
        $sitetree = SitetreeTable::getInstance()->findOneById($page->sitetree_id);
        
        return $sitetree;
    }
    
    /**
     * @see ContentGroupType
     */
    public function handleContentGroupChanged() 
	{
    	$this->getPage()->handleContentChanged();
        siteManager::getInstance()->getCache()->remove('ContentGroup.' . $this->ContentGroup->id);
    }
}