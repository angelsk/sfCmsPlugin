<?php if (siteManager::getInstance()->checkLock()) : ?>
  <div class="error">The site is currently disabled; it is probably being updated.  Please note that this may affect content changes. Thank you for your patience.</div>
<?php endif; ?>

<?php
/**
 * Provides info about the current sitetree node
 */
if ($sitetree = $sf_data->getRaw('sitetree')) 
{
  echo "<div class='sitetreeInfo'>Page is ";

  if ($sitetree->is_hidden) 
  {
    echo '<span class="site_sitetree_hidden">' . __('Hidden') . '</span>';
  }
  
  if ($sitetree->is_locked) 
  {
    echo '<span class="site_sitetree_locked">' . __('Locked') . '</span>';
  }
  
  if ($sitetree->is_deleted) 
  {
      echo '<span class="site_sitetree_locked">' . __('Deleted') . '</span>';
  }
  
  if ($sitetree->is_active) 
  {
    echo '<span class="site_sitetree_published">' . __('Live') . '</span>';
  }
  else 
  {
    echo '<span class="site_sitetree_not_published">' . __('Not live') . '</span>';
  }

  if ('sitetree' == $sf_request->getParameter('module'))
  {
    if ($sitetree->isManagedModule()) 
    {
      echo link_to('Edit content for this page', $sitetree->getEditLink());
    }
  }
  else
  {
    echo link_to('Edit sitetree properties', 'sitetree/edit?id=' . $sitetree->id);
  }

  if ($sitetree->is_active && !siteManager::getInstance()->checkLock()) 
  {
      $manager = siteManager::getInstance();
      $culture = sfContext::getInstance()->getUser()->getCulture();
      $url     = $manager->generateCrossAppUrlFor($manager->getRoutingProxy()->generateInternalUrl($sitetree, '', array('sf_culture'=>$culture)), $manager->getManagedApp());
      
      if ($url) 
      {
        echo "&nbsp; | <a href='"  . esc_entities($url) . "' target=\"_blank\">View page on frontend</a>";
        
        // Check potential URL conflicts
        if ($sitetree->hasConflictedUrl($url, $culture)) echo '&nbsp; <span class="site_sitetree_not_published">' .__('* WARNING: Another page exists with this URL').'</span> &nbsp;';
      }
      
      if ($sf_data->offsetExists('item')) 
      {
        $item = $sf_data->getRaw('item');
        
        if ($item->is_active) 
        {
          if ($item->ListingCategory->id) 
          {
            $url = $manager->generateCrossAppUrlFor($manager->getRoutingProxy()->generateInternalUrl($sitetree, 'category_item', array('sf_culture'=>$culture, 'slug'=>$item->slug, 'category'=>$item->ListingCategory->slug)), $manager->getManagedApp());
          }
          else 
          { 
            $url = $manager->generateCrossAppUrlFor($manager->getRoutingProxy()->generateInternalUrl($sitetree, 'item', array('sf_culture'=>$culture, 'slug'=>$item->slug)), $manager->getManagedApp());
          }
          
          if ($url) 
          {
            echo "&nbsp; | <a href='"  . esc_entities($url) . "' target=\"_blank\">View item on frontend</a>";
          }
        }
      }
  }
  
  echo '</div>';
}
