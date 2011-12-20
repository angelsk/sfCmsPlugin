<?php
/**
 * Taken from the sfDoctrineTree plugin and modified a little
 */
$id = (isset($id) ? $id : 'assetTree');
$class = (isset($class) ? $class : '');
$options = (isset($options) ? $options : array());
$nodeRenderFunction = (isset($nodeRenderFunction) ? $nodeRenderFunction : 'default_node_render');
$liRenderFunction = (isset($liRenderFunction) ? $liRenderFunction : 'default_li_render');
$ulRenderFunction = (isset($ulRenderFunction) ? $ulRenderFunction : 'default_ul_render');
$sortTree = (isset($sortTree) ? $sortTree : false);
$selectedNode = (isset($selectedNode)) ? $selectedNode : null;
$parents = (isset($parents)) ? $parents : null;
$checkIfHidden = (isset($checkIfHidden)) ? $checkIfHidden : false;
$canBeDeleted = (isset($canBeDeleted)) ? $canBeDeleted : false;

$records = $sf_data->getRaw('records');

if (!function_exists('default_node_render'))
{
  /**
   * @param Doctrine_Record $node
   * @return string
   */
  function default_node_render($node)
  {
    return esc_entities($node->__toString());
  }
}

if (!function_exists('default_li_render'))
{
  /**
   * @param Doctrine_Record $node
   * @return string
   */
  function default_li_render($node, $selectedNode, $parents)
  {
    $identifier = array_values($node->identifier());

    //set class of 'first' on root node
    $class = '';
    if ($node->getNode()->isRoot()) $class = 'class="first"';

    return "<li id='node{$identifier[0]}' {$class}>";
  }
}

if (!function_exists('default_ul_render'))
{
  /**
   * @param Doctrine_Record $node
   * @return string
   */
  function default_ul_render($node)
  {
    return  "<ul>";
  }
}

if (!function_exists('tree_sort'))
{
  function tree_sort($a, $b)
  {
    if ($a['lft'] == $b['lft'])
    {
      return 0;
    }
    return ($a['lft'] < $b['lft'] ? -1 : 1);
  }
}

if (isset($records) && !empty($records) && count($records) > 0)
{
  if ($sortTree)
  {
    usort($records, 'tree_sort');
  }

  echo "<ul id='$id' class='$class'>\n";

  $startLevel = null;
  $prevLevel = null;

  foreach ($records as $record)
  {
    $parentDeleted = false;
    
    if ($canBeDeleted && $record->is_deleted) continue;
    
    if ($canBeDeleted) 
    {
      $parent = $record;
      
      while ($parent = $parent->getNode()->getParent()) 
      {
        if ($parent->is_deleted) $parentDeleted = true;
      }
    }
    
    if ($canBeDeleted && $parentDeleted) continue;
    
    $parentHidden = false;
    
    if ($checkIfHidden && ($record->is_hidden || !$record->is_active)) continue;
      
    if ($checkIfHidden) 
    {
      $parent = $record;
      
      while ($parent = $parent->getNode()->getParent()) 
      {
        if ($parent->is_hidden || !$parent->is_active) $parentHidden = true;
      }
    }
    
    if ($checkIfHidden && $parentHidden) continue;

    if ($startLevel === null) {
      $startLevel = $prevLevel = $record['level'];
    }
    
    if ($prevLevel > $startLevel && $record['level'] <= $prevLevel) {
      echo "</li>\n";
    }
    
    if ($record['level'] > $prevLevel) {
      echo $ulRenderFunction($record, $prevLevel) . "\n";
    }
    else if ($record['level'] < $prevLevel) {
      echo str_repeat("</ul>\n</li>\n", $prevLevel - $record['level']);
    }

    echo $liRenderFunction($record, $selectedNode, $parents);
    echo $nodeRenderFunction($record) . "\n";

    $prevLevel = $record['level'];
  }
  
  if ($prevLevel > $startLevel || $prevLevel == 0) {
    echo "</li>\n";
  }

  $openUls = ($prevLevel - $startLevel);
  
  if ($openUls > 0) {
    echo str_repeat("</ul></li>\n", $openUls);
  }
  
  echo "</ul>";
}