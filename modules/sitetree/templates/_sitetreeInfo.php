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

  echo link_to('Edit sitetree node', 'sitetree/edit?id=' . $sitetree->id);

  if ($sitetree->is_active) 
  {
      $manager = siteManager::getInstance();
      $culture = sfContext::getInstance()->getUser()->getCulture();
      $url = $manager->generateCrossAppUrlFor($manager->getRoutingProxy()->generateInternalUrl($sitetree, '', array('sf_culture'=>$culture)), $manager->getManagedApp());
      
      if ($url) 
      {
        echo "| <a href='"  . esc_entities($url) . "' target=\"_blank\">View page on frontend</a>";
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
            echo " | <a href='"  . esc_entities($url) . "' target=\"_blank\">View item on frontend</a>";
          }
        }
      }
  }

  echo '</div>';
}
