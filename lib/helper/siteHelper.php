<?php
/**
 * Sitetree helpers
 * @author Jo Carter <work@jocarter.co.uk>
 */

/**
 * This gets the routename to use for a given $sitetree.
 *
 * The $name argument is used when the sitetree's module has added additional
 * names links.
 *
 * Simple usage for linking to the default route for a sitetree node:
 *
 * internal_url_for_sitetree($contentPageSitetree);
 *
 * returns something like "@sitetree-route-name_"
 *
 * More complicated route, eg. for an item of a content listing page
 *
 * internal_url_for_sitetree($contentListingSitetree, 'item', array('slug' => 'mySlug'))
 *
 * returns something like "@sitetree-route-name_item?slug=mySlug"
 *
 * @param Sitetree $sitetree
 * @param string $name
 * @param array $params
 * @return string
 */
function internal_url_for_sitetree($sitetree, $name='', $params = array()) 
{
  if (is_string($sitetree)) 
  {
    // check is active node
    $sitetree = SitetreeTable::getInstance()->retrieveByRoutename($sitetree, true);
  }
  
  if (!$sitetree) return false;
    
  return siteManager::getInstance()->getRoutingProxy()->generateInternalUrl($sitetree, $name, $params);
}

/**
 * Link to a sitetree node
 *
 * @param mixed $sitetree An Sitetree::route_name or a Sitetree
 * @param string $displayName The text for the link
 * @param array $options link_to options
 * @return string
 */
function link_to_sitetree($sitetree, $displayName=null, $options=array()) 
{
  if (is_string($sitetree))  
  {
    // check is active node
    $sitetree = SitetreeTable::getInstance()->retrieveByRoutename($sitetree, true);
  }

  if (!$sitetree) return false;

  // If we want to not render links for hidden sitetrees
  if (isset($options['not_hidden']) && true == $options['not_hidden'] && $sitetree->is_hidden) 
  {
    return false;
  }
  
  // If it contains html in the $displayName
  $escapeTitle = !(isset($options['not_escaped']) && true == $options['not_escaped']); 
  
  if (isset($options['not_escaped'])) 
  {
    unset($options['not_escaped']);
  }
  
  $internalUrl = internal_url_for_sitetree($sitetree, '');

  if ($displayName === null) $displayName = $sitetree->getTitle();
  if ($escapeTitle) $displayName = htmlentities($displayName, null, 'utf-8', false);
  
  if (!isset($options['title'])) 
  {
      $linkTitle = $sitetree->getLinkTitle();
    
      if ('' != $linkTitle)  
      {
        if ($escapeTitle) $options['title'] = htmlentities($linkTitle, null, 'utf-8', false);
        else $options['title'] = $linkTitle;
      }
      else 
      {
        $options['title'] = strip_tags($displayName);
      }
  }

  return link_to($displayName, $internalUrl, $options);
}

/**
 * Gets the title for a sitetree node
 */
function title_for_sitetree($sitetree) 
{
  if (is_string($sitetree)) 
  {
      // check is active node
      $sitetree = sitetreeTable::getInstance()->retrieveByRoutename($sitetree, true);
  }
  
  if (!$sitetree) return false;

  $displayName = $sitetree->getTitle();

  return htmlentities($displayName, null, 'utf-8', false);
}

/**
 * Generate the rss URL for a listing
 * 
 * @param string/sitetree $sitetree
 */
function rss_for_sitetree($sitetree) 
{
  if (is_string($sitetree))  
  {
    // check is active node
    $sitetree = sitetreeTable::getInstance()->retrieveByRoutename($sitetree, true);
  }

  if (!$sitetree) return false;
  
  if ('listingDisplay' != $sitetree->target_module) 
  {
    return false;
  }
  
  $listing = listingTable::getInstance()->findOneByRouteName($sitetree->route_name);
  
  // Set in listing - e.g: Feedburner URL
  if ('' != $listing->getRssUrl()) 
  {
    return $listing->getRssUrl();
  }
  else if (listingManager::getInstance()->getRssEnabled($listing->type)) 
  {
    return url_for(internal_url_for_sitetree($sitetree, 'rss'), true);
  }
  
  else return false;
}

/**
 * Generate the atom URL for a listing
 * 
 * @param string/sitetree $sitetree
 */
function atom_for_sitetree($sitetree)
{
  if (is_string($sitetree))  
  {
    // check is active node
    $sitetree = SitetreeTable::getInstance()->retrieveByRoutename($sitetree, true);
  }

  if (!$sitetree) 
  {
    return false;
  }
  
  if ('listingDisplay' != $sitetree->target_module) 
  {
    return false;
  }
  
  $listing = listingTable::getInstance()->findOneByRouteName($sitetree->route_name);
  
  // Set in listing - e.g: Feedburner URL
  if ('' != $listing->getRssUrl()) 
  {
    return $listing->getRssUrl();
  }
  else if (listingManager::getInstance()->getRssEnabled($listing->type)) 
  {
    return url_for(internal_url_for_sitetree($sitetree, 'atom'), true);
  }
  else 
  {
    return false;
  }
}

/**
 * Version of symfony's cache() which uses the site cache instead.
 *
 * @param string $name
 * @param int $lifeTime
 * @return boolean
 */
function site_cache($name, $lifeTime = 86400) 
{
  if (!sfConfig::get('sf_cache')) 
  {
    // if we're not using the symfony cache, don't use the site one
    return null;
  }

  $cache = siteManager::getInstance()->getCache();

  if (sfConfig::get('site.cache.started')) 
  {
    throw new sfCacheException('Cache already started.');
  }

  $data = $cache->get($name);

  if ($data === null) 
  {
    sfConfig::set('site.cache.started', true);
    sfConfig::set('site.cache.name', $name);
    sfConfig::set('site.cache.lifetime', $lifeTime);

    ob_start();
    ob_implicit_flush(0);

    return false;
  } 
  else 
  {
    echo $data;
    return true;
  }
}

/**
 * Version of symfony's cache_save() which uses the site cache instead.
 */
function site_cache_save() 
{
  if (!sfConfig::get('sf_cache')) 
  {
    // if we're not using the symfony cache, don't use the site one
    return null;
  }

  if (!sfConfig::get('site.cache.started')) 
  {
    throw new sfCacheException('Cache not started.');
  }

  $cache = siteManager::getInstance()->getCache();

  $data = ob_get_clean();
  $name = sfConfig::get('site.cache.name');
  $lifetime = sfConfig::get('site.cache.lifetime');

  $cache->set($name, $data, $lifetime);

  sfConfig::set('site.cache.started', false);

  echo $data;
}

/**
 * Make the input XML friendly - e.g: escape utf-8 html encoded characters into their hex form
 * 
 * Used for the listing RSS feeds
 * 
 * @param string $content
 */
function xml_character_encode($content = "") 
{
  $content = html_entity_decode($content, null, 'utf-8');
  $contents = unicode_string_to_array($content);
  $swap = "";
  $iCount = count($contents);

  for ($o=0;$o<$iCount;$o++) 
  {
    $contents[$o] = unicode_entity_replace($contents[$o]);
    $swap .= $contents[$o];
  }

  return mb_convert_encoding($swap, "UTF-8"); 
}

function unicode_string_to_array($string) 
{ 
  $strlen = mb_strlen($string);

  while ($strlen) 
  {
    $array[] = mb_substr( $string, 0, 1, "UTF-8" );
    $string = mb_substr( $string, 1, $strlen, "UTF-8" );
    $strlen = mb_strlen( $string );
  }

  return $array;
}

function unicode_entity_replace($c) 
{ 
  $h = ord($c{0});   

  if ($h <= 0x7F || $h < 0xC2) 
  {
    switch ($c) 
    {
      case '<':
        return '&lt;';
      case '>':
        return '&gt;';
      case '&':
        return '&amp;';
      case '"':
        return '&quot;';
      default:
         return $c;
    }
  }

  if ($h <= 0xDF) 
  {
    $h = ($h & 0x1F) << 6 | (ord($c{1}) & 0x3F);
    $h = "&#" . $h . ";";
    return $h;
  } 
  else if ($h <= 0xEF) 
  {
    $h = ($h & 0x0F) << 12 | (ord($c{1}) & 0x3F) << 6 | (ord($c{2}) & 0x3F);
    $h = "&#" . $h . ";";
    return $h;
  } 
  else if ($h <= 0xF4) 
  {
    $h = ($h & 0x0F) << 18 | (ord($c{1}) & 0x3F) << 12 | (ord($c{2}) & 0x3F) << 6 | (ord($c{3}) & 0x3F);
    $h = "&#" . $h . ";";
    return $h;
  }
}
