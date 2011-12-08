<?php

/**
 * ContentBlocks for the intro of a listing page
 */
class ContentGroupTypeListing extends ContentGroupType 
{
    /**
     * @var listing
     */
    protected $listing = null;
    
    /**
     * Get the listing that this Content group is for
     *
     * @return listing
     */
    public function getContentBlockListing() 
	{
        if ($this->listing === null) 
		{
            $this->listing = ListingTable::getInstance()->findOneByContentGroupId($this->ContentGroup->id);
            
            if (!$this->listing) 
			{
                throw new sfException("Missing a listing");
            }
        }
        
        return $this->listing;
    }
    
    /**
     * @see ContentGroupType
     */
    public function getContentBlockDefinitions() 
	{
        $type = $this->getContentBlockListing()->type;

        return listingManager::getInstance()->getTemplateContentBlockDefinitions($type);
    }
    
    /**
     * @see ContentGroupType
     */
    public function getEditUrl() 
	{
        return 'listingAdmin/edit?id=' . $this->getContentBlockListing()->id;
    }    
    
    /**
     * @see ContentGroupType
     */
    public function getPreviewUrl() 
	{
        //todo: named routes
        return 'listingDisplay/preview?id=' . $this->getContentBlockListing()->id;
    }
    
    /**
     * @see ContentGroupType
     */
    public function getSitetree() 
	{
        $listing = $this->getContentBlockListing();
        
        return sitetreeTable::getInstance()->findOneById($listing->sitetree_id);
    }
    
    /**
     * @see ContentGroupType
     */
    public function handleContentBlockGroupChanged() 
	{
        $this->getContentBlockListing()->handleContentChanged();
    }
}