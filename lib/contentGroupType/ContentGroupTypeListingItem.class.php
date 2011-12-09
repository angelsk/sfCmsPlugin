<?php
/**
 * Content blocks for the intro of a Listing page
 */
class ContentGroupTypeListingItem extends ContentGroupType 
{
    /**
     * @var Listing
     */
    protected $listingItem = null;
    
    /**
     * Get the Listing that the item is from
     *
     * @return Listing
     */
    public function getListing() 
	{
        return $this->getListingItem()->Listing;
    }
    
    /**
     * Set the Listing item this is from.
     * 
     * This is sometimes used for efficiency, this can always locate 
     * this Listing it's from if it needs to.
     *
     * @param Listing $ListingItem
     */
    public function setListingItem($ListingItem) 
	{
        $this->listingItem = $ListingItem;
    }
    
    /**
     * Get the Listing item this Content block is for
     *
     * @return ListingItem
     */
    public function getListingItem() 
	{
        if ($this->listingItem === null) 
		{
            $itemClass = $this->ContentGroup->type_options;
            $this->listingItem = Doctrine::getTable($itemClass)->findOneByContentGroupId($this->ContentGroup->id);
            
            if (!$this->listingItem) 
			{
                throw new sfException("Missing a Listing item");
            }
        }
        
        return $this->listingItem;
    }
    
    /**
     * @see ContentGroupType
     */
    public function getContentBlockDefinitions() 
	{
        $type = $this->getListing()->type;
        return ListingManager::getInstance()->getItemContentBlockDefinitions($type);
    }
    
    /**
     * @see ContentGroupType
     */
    public function getEditUrl() 
	{
        $listId = $this->getListingItem()->Listing_id;
        $itemId = $this->getListingItem()->id;
        
        return "listingAdmin/editItem?listId=$listId&id=$itemId";
    } 
    
    /**
     * @see ContentGroupType
     */
    public function getPreviewUrl() 
	{
        $listId = $this->getListingItem()->Listing_id;
        $itemId = $this->getListingItem()->id;
        
        return "listingDisplay/previewItem?listId=$listId&itemId=$itemId";
    }
    
    public function getName() 
	{
        return $this->getSitetree()->getTitle() . ' > ' . $this->getListingItem()->getTitle();
    }
    
    /**
     * @see ContentGroupType
     */
    public function getSitetree() 
	{
        $listing = $this->getListing();
        
        return SitetreeTable::getInstance()->findOneById($listing->sitetree_id);
    }
    
    /**
     * @see ContentGroupType
     */
    public function handleContentGroupChanged() 
	{
        $this->getListingItem()->handleContentChanged();
    }
}