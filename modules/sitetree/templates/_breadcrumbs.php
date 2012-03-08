<?php
$breadcrumbs = (isset($breadcrumbs) ? $sf_data->getRaw('breadcrumbs') : array());
$allBreadCrumbs = array();

if (isset($sitetree) && $sitetree) 
{
  $sitetree = $sf_data->getRaw('sitetree');
  
  $allBreadCrumbs[] = $sitetree->getTitle();

  if (!$sitetree->getNode()->isRoot()) 
  {
    $parent = $sitetree;
    
    while (!$parent->getNode()->isRoot()) 
    {
      $parent = $parent->getNode()->getParent();
      
      if ($parent->isManagedModule()) 
      {
        $moduleDefinition = $parent->getModuleDefinition();
        if (isset($editNode)) 
        {
          $allBreadCrumbs[] = link_to($parent->getTitle(), "sitetree/edit?id=$parent->id");
        }
        else 
        {
          $allBreadCrumbs[] = link_to($parent->getTitle(), $moduleDefinition['admin_url'] . "?routeName=$parent->route_name&site=$parent->site");
        }
      }
      else 
      {
        $allBreadCrumbs[] = $parent->getTitle();
      }
    }
  }
  
  $allBreadCrumbs[] = link_to('Sitetree', 'sitetree/index');
  $allBreadCrumbs = array_reverse($allBreadCrumbs);
}

$allBreadCrumbs = array_merge($allBreadCrumbs, $breadcrumbs);
?>

<div id="breadcrumbs">
  <?php echo implode(' &gt; ', $allBreadCrumbs); ?>
</div>