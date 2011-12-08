<?php

/**
 * ListingCategory
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    site_cms
 * @subpackage model
 * @author     Jo Carter
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class PluginListingCategory extends BaseListingCategory 
{	
	public function getItemCount() 
	{
		$count = array();
		$count['active'] = $this->getTable()->getActiveCount($this->id, true);
		$count['inactive'] = $this->getTable()->getActiveCount($this->id, false);
		$count['hidden'] = $this->getTable()->getHiddenCount($this->id);
		
		return $count;
	}
	
	public function delete(Doctrine_Connection $conn = null) 
	{
		// delete translations
	    $translations = $this->Translation;
	    foreach ($translations as $lang => $translation) 
		{
	    	$translation->delete();
	    	$translation->free();	
	    }
	    
	    parent::delete($conn);
	}
}
