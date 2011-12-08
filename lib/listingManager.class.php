<?php
class listingManager 
{	
	/**
	 * Current instance
	 *
	 * @var listingManager
	 */
	protected static $instance;
	
	/**
	 * Return instance or create new
	 * 
	 * @return listingManager
	 */
	public static function getInstance() 
	{
		if (!self::$instance) 
		{
			$class = sfConfig::get('app_site_listing_manager_class', 'listingManager');
			
			if (!class_exists($class)) 
			{
				throw new sfException("Could not make a config manager, class '$class' did not exist.  Check app_listing_manager_class in your app.yml");
			}
			
			self::$instance = new $class();
		}
		
		return self::$instance;
	}
	
	/**
	 * Get an array of the listing definitions we have available
	 *
	 * @return array
	 */
	public function getTypeDefinitions() 
	{
		return sfConfig::get('app_site_listing_types', array());
	}
	
	/**
	 * Get the type definition for the given parameter
	 *
	 * @param string $type
	 * @return array
	 */
	public function getTypeDefinitionParameter($type, $param, $default = null) 
	{
		$defns = $this->getTypeDefinition($type);
		
		return (isset($defns[$param]) ? $defns[$param] : $default);
	}
	
	/**
	 * Get a type/name pair array of the available listing types
	 *
	 * Some restrictions can be applied to template definitions:
     * Example config:
   
     blogs:
        name: Blogs
        restricted: true           # Only show this template on the specified route name (must have route_name(s) set)
        route_name: blogs          # Template for only use with specified route name
      generic_listing:
        name: Generic Listing

	 * $param string $type
	 * @return array Template defns
	 */
	public function getTypeList($listing) 
	{
		$types = $this->getTypeDefinitions();
		$out = array();
		
		if ($listing) 
		{
		    $currentType = $listing->type;
		    $currentRouteName = $listing->Sitetree->route_name;
	    }
	    else 
		{
	    	$currentType = false;
	    	$currentRouteName = siteManager::getInstance()->getCurrentSitetreeNode()->route_name;
	    }
		
		foreach ($types as $type => $defn) 
		{
      		// If restricted, then only return that one template
      		if (isset($defn['restricted']) && true == $defn['restricted'] && isset($defn['route_name']) 
           			&& ((is_array($defn['route_name']) && in_array($currentRouteName, $defn['route_name']))
            		|| (!is_array($defn['route_name']) && $currentRouteName == $defn['route_name']))) 
			{
        		$out = array();
        		$out[$type] = $defn['name'];
        		return $out;
      		}
        
			$out[$type] = $defn['name'];
		}
		
		asort($out);
		return $out;
	}
	
	/**
	 * Get the item statuses defined in the config, return empty array if none
	 * 
	 * Example: 
	 *  item_status:
          - featured
          - editors_pick
          - thought_about
          
     * @param string $type
     * @return array of statuses
	 */
	public function getItemStatusList($type) 
	{
		if (!$defn = $this->getTypeDefinition($type)) 
		{
			return array();
		}
		
		$choices = (isset($defn['item_status']) ? $defn['item_status'] : array());
		$formattedChoices = array();
		
		foreach ($choices as $choice) 
		{
			$formattedChoices[$choice] = ucfirst(str_replace('_',' ',$choice));
		}
		
		return $formattedChoices;
	}
	
	/**
	 * Get the content block definitions for the template
	 *
	 * @param string $type
	 * @return array
	 */
	public function getTemplateContentBlockDefinitions($type) 
	{
		if (!$defn = $this->getTypeDefinition($type)) 
		{
			return array();
		}
		
		$blocks = (isset($defn['listing_blocks']) ? $defn['listing_blocks'] : array());
		return $blocks;
	}
	
	/**
	 * Get the content block definitions to be used with the item itself
	 *
	 * @param string $type
	 * @return array
	 */
	public function getItemContentBlockDefinitions($type)
	{
		if (!$defn = $this->getTypeDefinition($type)) 
		{
			return array();
		}
		
		$blocks = (isset($defn['item_blocks']) ? $defn['item_blocks'] : array());
		return $blocks;
	}
	
	/**
	 * Get the type definition for the given type
	 *
	 * @param string $type
	 * @return array
	 */
	public function getTypeDefinition($type) 
	{
		$defns = $this->getTypeDefinitions();
		return (isset($defns[$type]) ? $defns[$type] : null);
	}
	
	/**
	 * Get the template directory
	 *
	 * @return string
	 */
	public function getTemplateDir() 
	{
		return sfConfig::get('app_site_listing_template_dir', sfConfig::get('sf_root_dir') . '/templates/listing');
	}
	
	/**
	 * Get the name of the listing template
	 *
	 * @param $type string
	 * @return string
	 */
	public function getListingTemplateName($type) 
	{
	    return $this->getTypeDefinitionParameter($type, 'listing_template', $type . '_listing');
	}
	
	/**
	 * Get the template file for the listing page
	 *
	 * @param string $type
	 * @return string
	 */
	public function getListingTemplateFile($type) 
	{
		return $this->getTemplateDir() . "/" . $this->getListingTemplateName($type) . ".php";
	}
	
	/**
	 * Get the name of the item template
	 *
	 * @param $type string
	 * @return string
	 */
	public function getItemTemplateName($type) 
	{
	    return $this->getTypeDefinitionParameter($type, 'item_template', $type . '_item');
	}
	
	/**
	 * Get the template file for the item details page
	 *
	 * @param string $type
	 * @return string
	 */
	public function getItemTemplateFile($type) 
	{
		return $this->getTemplateDir() . "/" . $this->getItemTemplateName($type) . ".php";
	}
	
	/**
	 * Get the pager class for the list items
	 *
	 * @param string $type
	 * @return string
	 */
	public function getListItemPagerClass($type) 
	{
		$defn = $this->getTypeDefinition($type);
		
		if (isset($defn['list_item_pager_class'])) 
		{
			return $defn['list_item_pager_class'];
		} 
		else 
		{
			return 'listingItemPager';
		}
	}
	
	/**
	 * Get the pager for displaying the items
	 *
	 * @param string $type
	 * @return string
	 */
	public function getDisplayPagerClass($type) 
	{
		$defn = $this->getTypeDefinition($type);
		
		if (isset($defn['display_pager_class'])) 
		{
			return $defn['display_pager_class'];
		} 
		else 
		{
			return 'listingDisplayPager';
		}
	}
	
	/**
	 * Get the list item ordering
	 *
	 * @param string $type
	 * @return string
	 */
	public function getListItemOrdering($type) 
	{
		$defn = $this->getTypeDefinition($type);
		
		if (isset($defn['list_item_ordering'])) 
		{
			return $defn['list_item_ordering'];
		} 
		else 
		{
			return 't.title';
		}
	}
	
	/**
	 * Get the class for the list items
	 *
	 * @param string $type
	 * @return string
	 */
	public function getListItemClass($type) 
	{
		$defn = $this->getTypeDefinition($type);
		
		if (isset($defn['list_item_class'])) 
		{
			return $defn['list_item_class'];
		} 
		else 
		{
			return 'ListingItem';
		}
	}
	
	/**
	 * Get the form class for creating/editing a list item
	 *
	 * @param string $type
	 * @return string
	 */
	public function getListItemFormClass($type) 
	{
		$defn = $this->getTypeDefinition($type);
		
		if (isset($defn['list_item_form_class'])) 
		{
			return $defn['list_item_form_class'];
		} 
		else 
		{
			return 'ListingItemForm';
		}
	}
	
	/**
	 * Get the ordering for the RSS feed
	 * 
	 * @param string $type
	 * @return string
	 */
	public function getRssItemOrdering($type) 
	{
    	$defn = $this->getTypeDefinition($type);
    	
    	if (isset($defn['rss_item_ordering'])) 
		{
      		return $defn['rss_item_ordering'];
    	} 
    	else 
		{
      		return 'i.created_at DESC';
    	}
  	}
  
  	/**
  	 * Is RSS enabled for this listing?

  	 * @param string $type
  	 * @return string
  	 */
  	public function getRssEnabled($type) 
	{
    	$defn = $this->getTypeDefinition($type);
    
    	if (isset($defn['rss_enabled'])) 
		{
      		return $defn['rss_enabled'];
    	} 
    	else 
		{
      		return false;
    	}
  	}
}