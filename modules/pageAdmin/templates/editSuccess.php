<?php
$sitetree = $sf_data->getRaw('sitetree');
$page = $sf_data->getRaw('page');
$contentGroup = $sf_data->getRaw('contentGroup');

sfContext::getInstance()->getResponse()->setTitle(htmlentities('Editing page' . ' - ' . $sitetree->title, null, 'utf-8', false), false);

slot('breadcrumbs', get_partial('sitetree/breadcrumbs', array(
	'sitetree' => $sitetree
)));
?>

<div class="item_control">

  <h2><?php echo $sitetree->getTitle(); ?></h2>
  
  <?php echo include_partial('sitetree/sitetreeInfo', array('sitetree'=>$sitetree)); ?>
  
  <div class='sitetreeInfo'>
	  Template is
	  <span class="site_sitetree_<?php if (!$sitetree->is_active) echo 'not_'; ?>published">
	    '<?php $defn = pageManager::getInstance()->getTemplateDefinition($page->template); echo $defn['name']; ?>'
	  </span>
	  <?php if (!$sitetree->is_locked) : ?>
	    [<?php echo link_to('change template', 'pageAdmin/editTemplate?id=' . $page->id)?>]
	  <?php endif; ?>
  </div>

  <?php
  $url = 'sitetree/index';
  include_component('contentAdmin', 'editor', array('contentGroup' => $contentGroup, 'cancelUrl'=>$url));
  ?>
</div>
