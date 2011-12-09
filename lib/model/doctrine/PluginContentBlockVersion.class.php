<?php

/**
 * ContentBlockVersion
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    site_cms
 * @subpackage model
 * @author     Jo Carter
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class PluginContentBlockVersion extends BaseContentBlockVersion 
{
  /**
     * @var ContentBlockType
     */
    protected $typeObj = null;

    /**
     * @see mergeParameters()
     * @var array
     */
    protected $additionalParameters = array();

    /**
     * Called when object cloned so don't get funny stuff happening with the Multiple type
     */
    public function __clone()
    {
      $this->ContentBlock = clone $this->ContentBlock;
      $this->CurrentVersion = clone $this->CurrentVersion;
    }
    
  /**
     * Make a new version and create the "current version" entry
     *
     * @param ContentBlock $contentBlock
     * @param string $lang
     * @return ContentBlockVersion
     */
    public static function createInitialVersion($contentBlock, $lang) 
  {
        // make version for the current lang
        $contentBlockVersion = self::createVersion($contentBlock, $lang);

        // make our created version the current one
        $contentBlockCurrentVersion = new ContentBlockCurrentVersion();
        $contentBlockCurrentVersion->lang = $lang;
        $contentBlockCurrentVersion->ContentBlock = $contentBlock;
        $contentBlockCurrentVersion->Version = $contentBlockVersion;
        $contentBlockCurrentVersion->content_block_version_id = $contentBlockVersion->id;
        $contentBlockCurrentVersion->save();

        return $contentBlockVersion;
    }
    
   /**
     * @param ContentBlock $contentBlock
     * @param string $lang
     * @return ContentBlockVersion
     */
    public static function createVersion($contentBlock, $lang) 
  {
        $contentBlockVersion = new ContentBlockVersion();
        $contentBlockVersion->lang = $lang;
        $contentBlockVersion->ContentBlock = $contentBlock;
        $contentBlockVersion->save();

        return $contentBlockVersion;
    }
    
    /**
     * Get the type class for this Content block.
     *
     * This is what deals with the rendering of this Content block.
     *
     * @return ContentBlockType
     */
    public function getContentBlockType() 
  {
        if ($this->typeObj === null) 
    {
            $class = 'ContentBlockType' . $this->ContentBlock->type;

            if (!class_exists($class)) 
      {
                throw new sfException("There is no type class, " . $class);
            }
            
            $this->typeObj = new $class($this);
        }

        return $this->typeObj;
    }
    
  /**
     * Is this the currently live version?
     */
    public function isCurrent() 
  {
        return ($this->ContentBlock->getVersionForLang($this->lang)->id == $this->id);
    }

    /**
     * Is this the newest Content block version for this Content block/lang?
     */
    public function isNewest() 
  {
        return ($this->ContentBlock->getNewestVersionForLang($this->lang)->id == $this->id);
    }
    
    /**
     * Adds additional parameters which will be used by the "getDefinition()"
     * method
     *
     * @param array $params
     */
    public function mergeParameters($params) 
  {
        // Allow priority for new params if Content block rendered several times on page
        $this->additionalParameters = $params + $this->additionalParameters;
    }

    /**
     * Get parameters for rendering, editing etc.
     *
     * @return array
     */
    public function getDefinition() 
  {
      $contentBlockDefn = $this->ContentBlock->getDefinition();

      if ($contentBlockDefn) return array_merge($contentBlockDefn, $this->additionalParameters);
      else return $this->additionalParameters;
    }
}
