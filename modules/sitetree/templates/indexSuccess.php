<?php
$treeNodes = $sf_data->getRaw('treeNodes');

slot('breadcrumbs', get_partial('sitetree/breadcrumbs', array(
  'breadcrumbs' => array(link_to('Sitetree', 'sitetree/index'))
)));

// use this to store a global var for our tree rendering functions
sfConfig::set('site_hack_entireSitetree', $treeNodes);
?>

<div id="sf_admin_container">

  <h1>Sitetree</h1>
  
  <div id="sf_admin_header">
    <p>Click on the page name to edit the content; and the pencil icon to edit the properties (and image) of each page.</p>
  </div>

  <?php if ($sf_user->hasFlash('error')) : ?>
    <div class="error"><?php echo $sf_user->getFlash('error'); ?></div>
  <?php endif; ?>
  
  <?php if ($sf_user->hasFlash('notice')) : ?>
    <div class="notice"><?php echo $sf_user->getFlash('notice'); ?></div>
  <?php endif; ?>
  
  <div id="sf_admin_content">
    <div class="sf_admin_list">
      <?php if (count($treeNodes) > 0) 
      {
        function sitetree_manager_node($sitetree) 
        {
          $user       = sfContext::getInstance()->getUser();
          $canAdmin   = $user->hasCredential('site.admin');
          $canPublish = ($canAdmin || $user->hasCredential('site.publish'));
          
          $name = esc_entities($sitetree->title);
          $out = "<span class=\"lnk\">" . $name . "</span>";
          $class = '';
          $content = '';
      
          if ($sitetree->isManagedModule()) 
          {
            $moduleDefinition = $sitetree->getModuleDefinition();
            
            if ('sitetree/index' != $moduleDefinition['admin_url'] && !$sitetree->is_deleted)
            {
              $out = '<span class="lnk">' . link_to(
                $name,
                $moduleDefinition['admin_url'] . "?routeName=$sitetree->route_name&site=$sitetree->site"
              ) . "</span>";
            }
          }
      
          if ($sitetree->is_hidden && !$sitetree->is_deleted) 
          {
            $class .= ' hidden';
            $content .= ' HIDDEN';
          }
      
          if ($sitetree->is_locked && !$sitetree->is_deleted) 
          {
            $class .= ' locked';
            $content .= ' LOCKED';
          }
          
          if (!$sitetree->is_active && !$sitetree->is_deleted) 
          {
            $class .= ' notpublished';
            $content .= ' NOT LIVE';
          }
          
          if ($sitetree->is_deleted) 
          {
            $class .= ' deleted';
            $content .= ' DELETED';
          }
          
          if (empty($class)) $class = 'blank';
          
          $out .= '<span class="nodeinfo '.trim($class).'">'.trim($content).'&nbsp;</span>';
          
          $out .= '<span class="sitetree_actions">';
          
          $node = $sitetree->getNode();
          $isRoot = $node->isRoot();
          $spacer = '<span class="spacer">&nbsp;</span>';
          
          if (!$isRoot)
          {
            $out .= '<a href="' . url_for('sitetree/changeParent?id='.$sitetree->id) . '" title="change parent"><img src="/sfCmsPlugin/images/parent.png" /></a>';
          }
          else $out .= $spacer;
          if (!$isRoot && $node->hasPrevSibling()) 
          {
            $out .= '<a href="' . url_for('sitetree/move?direction=up&id='.$sitetree->id) . '" title="move up"><img src="/sfCmsPlugin/images/up.png" /></a>';
          }
          else $out .= $spacer;
          if (!$isRoot && $node->hasNextSibling()) 
          {
            $out .= '<a href="' . url_for('sitetree/move?direction=down&id='.$sitetree->id) . '" title="move down"><img src="/sfCmsPlugin/images/down.png" /></a>';
          }
          else $out .= $spacer;
          if (!$isRoot && !$sitetree->is_locked && $canAdmin) 
          {
            $out .= '<a href="' . url_for('sitetree/delete?id='.$sitetree->id) . '" title="delete" class="delete_sitetree"><img src="/sfCmsPlugin/images/cross.png" /></a>';
          }
          else if ($canAdmin) $out .= $spacer; // otherwise space if just no permission
          if ($sitetree->is_deleted && $user->isSuperAdmin()) 
          {
            $out .= '<a href="' . url_for('sitetree/restore?id='.$sitetree->id) . '" title="restore"><img src="/sfCmsPlugin/images/restore.png" /></a>';
          }
          if (!$sitetree->is_deleted) 
          {
            $out .= '<a href="' . url_for('sitetree/edit?id='.$sitetree->id) . '" title="edit properties"><img src="/sfCmsPlugin/images/edit.png" /></a>';
          }
          else $out .= $spacer;
          if (!$sitetree->is_deleted) 
          {
            $out .= '<a href="' . url_for('sitetree/create?parent='.$sitetree->id) . '" title="add child page"><img src="/sfCmsPlugin/images/add.png" /></a>';
          }
          else $out .= $spacer;
          if ($canPublish && !$sitetree->is_active && !$sitetree->is_deleted) 
          {
            $out .= '<a href="' . url_for('sitetree/publish?id='.$sitetree->id) . '" title="publish page"><img src="/sfCmsPlugin/images/publish.png" /></a>';
          }
          $out .= '</span>';
      
          return $out;
        }
      
        function sitetree_manager_li($node) 
        {
          $class = '';
          $class .= ($node->is_locked ? ' locked' : '');
          $class .= (!$node->is_active ? ' notlive' : '');
          $class .= ($node->getNode()->isRoot() ? ' root' : '');
          $class .= ($node->is_deleted ? ' deleted' : '');
          
          return "<li class='$class' id='sitetree_{$node->id}'>";
        }
      
        // render the tree
        include_partial(
          'sitetree/tree',
          array(
            'id' => 'sitetree_manager_sitetree',
            'class' => 'folderTree',
            'records' => $treeNodes,
            'nodeRenderFunction' => 'sitetree_manager_node',
            'liRenderFunction' => 'sitetree_manager_li',
            'sf_cache_key' => 'sitetree',
            'canBeDeleted' => (!$sf_user->isSuperAdmin())
          )
        );
      } ?>
    </div>
    
    <?php if (isset($sites) && 0 < count($sites)) : // only have root so offer other sites to copy structure from ?>
    
      <?php include_partial('sitetree/copySite', array('sites'=>$sites)); ?>
    
    <?php endif; ?>
  </div>
</div>
