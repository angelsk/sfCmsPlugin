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
  public function getTemplateDefinitions()
  {
    return sfConfig::get('app_site_listing_templates', array());
  }

  /**
   * Get the template definition for the given parameter
   *
   * @param string $template
   * @return array
   */
  public function getTemplateDefinitionParameter($template, $param, $default = null)
  {
    $defns = $this->getTemplateDefinition($template);

    return (isset($defns[$param]) ? $defns[$param] : $default);
  }

  /**
   * Get a template/name pair array of the available listing templates
   *
   * Some restrictions can be applied to template definitions:
   * Example config:
    
       blogs:
         name: Blogs
         restricted: true           # Only show this template on the specified route name (must have route_name(s) set)
         route_name: blogs          # Template for only use with specified route name
         
       generic_listing:
         name: Generic Listing
         site: [gb,fr]              # Only allow template on these sites

   * @param Listing $listing
   * @return array Template defns
   */
  public function getPossibleTemplatesForListing($listing)
  {
    $templates = $this->getTemplateDefinitions();
    $out       = array();

    if ($listing)
    {
      $currentTemplate  = $listing->template;
      $currentRouteName = $listing->Sitetree->route_name;
    }
    else 
    {
      $currentTemplate  = false;
      $sitetree         = siteManager::getInstance()->getCurrentSitetreeNode();
      $currentRouteName = ($sitetree ? $sitetree->route_name : false);
    }
    
    $currentSite = siteManager::getInstance()->getCurrentSite();
    
    foreach ($templates as $templateSlug => $definition) 
    {
      $skip = ($templateSlug === $currentTemplate); // if we already have this template set allow it
      
      // Check whether allowed site for this template
      $sites = (isset($definition['site']) ? (is_array($definition['site']) ? $definition['site'] : array($definition['site'])) : array());
      
      if (!empty($sites) && !in_array($currentSite, $sites) && !$skip) continue;
      
      // Check whether route name restriction
      $templateRoutes = (isset($definition['route_name']) ? (is_array($definition['route_name']) ? $definition['route_name'] : array($definition['route_name'])) : array());
      
      // If restricted to a certain route name, then only return that one template
      if (isset($definition['restricted']) && true == $definition['restricted'] 
            && isset($definition['route_name']) && in_array($currentRouteName, $templateRoutes)
            && !$skip)
      {
        $out = array();
        $out[$templateSlug] = $definition['name'];
        return $out;
      }
      
      // If not restricted, but template only for certain routes
      if (!empty($templateRoutes) && !in_array($currentRouteName, $templateRoutes)) continue;
      
      $out[$templateSlug] = $definition['name'];
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

   * @param string $template
   * @return array of statuses
   */
  public function getItemStatusList($template)
  {
    if (!$defn = $this->getTemplateDefinition($template))
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
   * @param string $template
   * @return array
   */
  public function getTemplateContentBlockDefinitions($template)
  {
    if (!$defn = $this->getTemplateDefinition($template))
    {
      return array();
    }

    $blocks = (isset($defn['listing_blocks']) ? $defn['listing_blocks'] : array());
    return $blocks;
  }

  /**
   * Get the content block definitions to be used with the item itself
   *
   * @param string $template
   * @return array
   */
  public function getItemContentBlockDefinitions($template)
  {
    if (!$defn = $this->getTemplateDefinition($template))
    {
      return array();
    }

    $blocks = (isset($defn['item_blocks']) ? $defn['item_blocks'] : array());
    return $blocks;
  }

  /**
   * Get the template definition for the given template
   *
   * @param string $template
   * @return array
   */
  public function getTemplateDefinition($template)
  {
    $defns = $this->getTemplateDefinitions();
    return (isset($defns[$template]) ? $defns[$template] : null);
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
   * @param $template string
   * @return string
   */
  public function getListingTemplateName($template)
  {
    return $this->getTemplateDefinitionParameter($template, 'listing_template', $template . '_listing');
  }

  /**
   * Get the template file for the listing page
   *
   * @param string $template
   * @return string
   */
  public function getListingTemplateFile($template)
  {
    // check for site specific template version
    $siteVersion = sprintf('%s/%s/%s.php', $this->getTemplateDir(), siteManager::getInstance()->getCurrentSite(), $this->getListingTemplateName($template));
    
    if (is_file($siteVersion)) return $siteVersion;
    else return sprintf('%s/%s.php', $this->getTemplateDir(), $this->getListingTemplateName($template));
  }

  /**
   * Get the name of the item template
   *
   * @param $template string
   * @return string
   */
  public function getItemTemplateName($template)
  {
    return $this->getTemplateDefinitionParameter($template, 'item_template', $template . '_item');
  }

  /**
   * Get the template file for the item details page
   *
   * @param string $template
   * @return string
   */
  public function getItemTemplateFile($template)
  {
    // check for site specific template version
    $siteVersion = sprintf('%s/%s/%s.php', $this->getTemplateDir(), siteManager::getInstance()->getCurrentSite(), $this->getItemTemplateName($template));
    
    if (is_file($siteVersion)) return $siteVersion;
    else return sprintf('%s/%s.php', $this->getTemplateDir(), $this->getItemTemplateName($template));
  }

  /**
   * Get the pager class for the list items
   *
   * @param string $template
   * @return string
   */
  public function getListItemPagerClass($template)
  {
    $defn = $this->getTemplateDefinition($template);

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
   * @param string $template
   * @return string
   */
  public function getDisplayPagerClass($template)
  {
    $defn = $this->getTemplateDefinition($template);

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
   * @param string $template
   * @return string
   */
  public function getListItemOrdering($template)
  {
    $defn = $this->getTemplateDefinition($template);

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
   * @param string $template
   * @return string
   */
  public function getListItemClass($template)
  {
    $defn = $this->getTemplateDefinition($template);

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
   * @param string $template
   * @return string
   */
  public function getListItemFormClass($template)
  {
    $defn = $this->getTemplateDefinition($template);

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
   * @param string $template
   * @return string
   */
  public function getRssItemOrdering($template)
  {
    $defn = $this->getTemplateDefinition($template);

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

   * @param string $template
   * @return string
   */
  public function getRssEnabled($template)
  {
    $defn = $this->getTemplateDefinition($template);

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