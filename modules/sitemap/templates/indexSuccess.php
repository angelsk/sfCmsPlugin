<?php
$entireSitetree = $sf_data->getRaw('entireSitetree');

// render the entire sitetree
if (!function_exists('sitemap_node_render')) 
{
  function sitemap_node_render($sitetree) 
  {
    return link_to_sitetree($sitetree);
  }
}
?>

<h1>Sitemap</h1>

<div class="sitemap">
  <?php 
  include_partial(
    'sitemap/tree',
    array(
      'records' => $entireSitetree,
      'nodeRenderFunction' => 'sitemap_node_render',
      'class' => 'sitemap',
      'checkIfHidden' => true,
      'canBeDeleted' => false
    )
  );
  ?>
</div>
