<?php
/**
 * Content group type base
 * 
 * This is a base implementation for a Content group type.  It is responsible
 * for various aspects of how a Content group is handled:
 * 
 *  - The Content block definitions
 *  - Editing urls
 *  - Sitetree node the Content group is linked to
 *
 * Each time a Content group is used, it creates an instance of its ContentGroupType.  
 * So, after this class is constructed, setContentGroup() is always called with the 
 * Content group which creates it.
 * 
 * @see ContentGroup::getContentGroupType().
 * 
 * When you want to make a new Content group type, "foo", extend this class and 
 * name it ContentGroupTypeFoo.  
 */
abstract class ContentGroupType 
{
    protected $ContentGroup;

    /**
     * Get the Content group that this instance of the 
     *
     * @return ContentGroup
     */
    public function getContentGroup() 
	{
        return $this->ContentGroup;
    }

    /**
     * Set the Content group
     * 
     * This is always called by the Content group when it initialises its type
     * class in ContentGroup::getContentGroupType()
     *
     * @param ContentGroup $ContentGroup
     */
    public function setContentGroup($ContentGroup) 
	{
        $this->ContentGroup = $ContentGroup;
    }
    
	/**
     * Get the Content block definitions for this Content group.
     * 
     * This is an array of Content block definition arrays, indexed by slugs.  Each 
     * Content block definition must have a name and type, along with any parameters
     * unique to the type.  
     * For example:
     * 
     * array(
     *     'intro' => array(
     *         'name' => 'Intro Content block',
     *         'type' => 'HTML'
     *     ),
     *     'h1' => array(
     *         'name' => 'H1 Content block',
     *         'type' => 'Text'
     *     )
     * )
     */
    abstract public function getContentBlockDefinitions();

    /**
     * Get an internal symfony url for editing this Content group on the backend
     *
     * @return string
     */
    abstract public function getEditUrl();

    /**
     * Get the url for previewing this on the frontend
     *
     * @return string
     */
    abstract public function getPreviewUrl();

    /**
     * Get the sitetree node for this type
     * 
     * @return string
     */
    abstract public function getSitetree();

    /**
     * Get the name of this Content group
     *
     * @return string
     */
    public function getName() {
        return $this->getSitetree()->getTitle();
    }

    /**
     * Get the cultures we are allowed for this Content group
     *
     * @return string[]
     */
    public function getCultures() 
	{
        // return cultures for the sitetree's site
        $sitetree = $this->getSitetree();
        $siteDef = siteManager::getInstance()->getSite();
        $cultures = $siteDef['cultures'];
        $cultures = array();
        
        foreach ($cultures as $culture) 
		{
            $cultures[$culture] = $culture;
        }
        
        return $cultures;
    }
    
    /**
     * This gets called when the live Content of the Content group has been changed.
     * 
     * It can be used to clear frontend cache files etc.
     */
    public function handleContentGroupChanged() 
	{  
    }
}